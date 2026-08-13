<?php

namespace Tests\Feature\ImportExport;

use App\Exceptions\ImportHasInvalidRowsException;
use App\Models\ImportBatch;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\ImportExport\SiswaImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * All-or-nothing: satu berkas import ditolak seluruhnya kalau ada satu baris
 * saja yang tidak valid — persis requirement dosen penguji ("jangan lakukan
 * entry data yang valid"). Gerbangnya ada di SiswaImportService::confirm(),
 * bukan di frontend, jadi test ini memanggil service langsung, meniru
 * seolah pengguna melewati UI (mis. lewat curl langsung ke endpoint confirm).
 */
class ImportAllOrNothingTest extends TestCase
{
    private const HEADER = 'nis,nisn,nama,jenis_kelamin,tempat_lahir,tanggal_lahir,agama,alamat,jenjang,kelas,kategori,status,keterangan_siswa';

    private User $admin;

    private int $branchId;

    private SiswaImportService $service;

    private Kelas $kelas;

    private Kategori $kategori;

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::table('import_batches')->delete();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->branchId = $this->admin->branch_id;
        $this->service = app(SiswaImportService::class);

        TahunAjaran::factory()->aktif()->create(['branch_id' => $this->branchId]);

        $this->kelas = Kelas::factory()->create([
            'branch_id' => $this->branchId,
            'jenjang' => 'MI',
            'level' => 1,
        ]);
        $this->kategori = Kategori::factory()->create(['branch_id' => $this->branchId]);
    }

    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'nis' => '000101',
            'nisn' => '1234567890',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Pontianak',
            'tanggal_lahir' => '2015-05-20',
            'agama' => 'Islam',
            'alamat' => 'Jl. Merdeka No. 1',
            'jenjang' => 'MI',
            'kelas' => $this->kelas->nama,
            'kategori' => $this->kategori->nama,
            'status' => 'Aktif',
            'keterangan_siswa' => '',
        ], $overrides);
    }

    private function csv(array $rows): UploadedFile
    {
        $lines = array_map(fn (array $row): string => implode(',', $row), $rows);
        $content = self::HEADER."\n".implode("\n", $lines)."\n";

        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'siswa.csv', 'text/csv', null, true);
    }

    public function test_confirm_rejects_the_whole_file_when_any_row_is_invalid(): void
    {
        $preview = $this->service->validate(
            $this->csv([
                $this->validRow(['nis' => '000101', 'nisn' => '1111111111']),
                $this->validRow(['nis' => '000102', 'nisn' => '2222222222']),
                $this->validRow(['nis' => '000103', 'nisn' => '3333333333', 'nama' => '']),
            ]),
            $this->branchId
        );

        $this->assertSame(2, $preview->validRows);
        $this->assertSame(1, $preview->errorRows);

        $this->expectException(ImportHasInvalidRowsException::class);

        try {
            $this->service->confirm($preview->previewId, $this->branchId, $this->admin->id);
        } finally {
            // Tidak ada baris yang tersimpan — bukan cuma baris error-nya.
            $this->assertSame(0, Siswa::count());
            $this->assertSame(0, ImportBatch::count());
        }
    }

    public function test_confirm_succeeds_once_the_invalid_row_is_fixed(): void
    {
        $preview = $this->service->validate(
            $this->csv([
                $this->validRow(['nis' => '000101', 'nisn' => '1111111111']),
                $this->validRow(['nis' => '000102', 'nisn' => '2222222222', 'nama' => '']),
            ]),
            $this->branchId
        );

        $this->assertSame(1, $preview->errorRows);

        $fixed = $this->service->patchRow(
            $preview->previewId,
            1, // index of the second row (0-based)
            ['nama' => 'Siti Aminah'],
            $this->branchId
        );

        $this->assertSame(0, $fixed->errorRows);
        $this->assertSame(2, $fixed->validRows);

        $batch = $this->service->confirm($preview->previewId, $this->branchId, $this->admin->id);

        $this->assertSame('completed', $batch->status);
        $this->assertSame(2, $batch->success_count);
        $this->assertSame(2, Siswa::count());
        $this->assertDatabaseHas('siswas', ['nis' => '000102', 'nama' => 'Siti Aminah']);
    }

    public function test_confirm_rejects_when_preview_belongs_to_another_branch(): void
    {
        $otherBranchAdmin = User::factory()->admin()->create(['username' => 'admin-other-branch']);

        $preview = $this->service->validate(
            $this->csv([$this->validRow()]),
            $this->branchId
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->service->confirm($preview->previewId, $otherBranchAdmin->branch_id, $otherBranchAdmin->id);
    }
}
