<?php

namespace Tests\Feature\ImportExport;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\ImportExport\SiswaImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Perbaikan inline (SiswaImportService::patchRow()) — jalur "langsung dari
 * aplikasi" yang diminta dosen sebagai alternatif upload ulang Excel.
 */
class ImportRowPatchTest extends TestCase
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

    public function test_patch_row_revalidates_the_whole_file_and_keeps_preview_id(): void
    {
        $preview = $this->service->validate(
            $this->csv([$this->validRow(['nama' => ''])]),
            $this->branchId
        );

        $this->assertSame(1, $preview->errorRows);

        $patched = $this->service->patchRow(
            $preview->previewId,
            0,
            ['nama' => 'Ahmad Fauzi'],
            $this->branchId
        );

        $this->assertSame($preview->previewId, $patched->previewId);
        $this->assertSame(0, $patched->errorRows);
        $this->assertSame(1, $patched->validRows);
    }

    /**
     * Memperbaiki satu baris bisa memunculkan error baru pada baris lain —
     * di sini, memperbaiki NIS baris pertama membuatnya bentrok dengan NIS
     * baris kedua yang sebelumnya tidak apa-apa.
     */
    public function test_patch_row_can_introduce_a_new_duplicate_error_on_another_row(): void
    {
        $preview = $this->service->validate(
            $this->csv([
                $this->validRow(['nis' => '000101', 'nisn' => '1111111111', 'nama' => '']),
                $this->validRow(['nis' => '000102', 'nisn' => '2222222222']),
            ]),
            $this->branchId
        );

        $this->assertSame(1, $preview->errorRows);

        $patched = $this->service->patchRow(
            $preview->previewId,
            0,
            ['nis' => '000102', 'nama' => 'Ahmad Fauzi'],
            $this->branchId
        );

        // Baris pertama (yang diperbaiki) jadi valid, tapi sekarang bentrok
        // dengan baris kedua — total error tetap 1, tapi baris yang error
        // berpindah dari baris pertama ke baris kedua.
        $this->assertSame(1, $patched->errorRows);
        $this->assertSame(1, $patched->invalidRows[0]['index']);
        $pesan = implode(' ', array_column($patched->errors, 'message'));
        $this->assertStringContainsString('NIS duplikat dalam file', $pesan);
    }

    public function test_patch_row_rejects_unknown_preview_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sesi preview telah kedaluwarsa. Silakan upload ulang file.');

        $this->service->patchRow('00000000-0000-0000-0000-000000000000', 0, [], $this->branchId);
    }

    public function test_patch_row_rejects_out_of_range_row_index(): void
    {
        $preview = $this->service->validate($this->csv([$this->validRow()]), $this->branchId);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Baris tidak ditemukan dalam sesi preview.');

        $this->service->patchRow($preview->previewId, 5, [], $this->branchId);
    }

    public function test_patch_row_rejects_preview_from_another_branch(): void
    {
        $otherBranchAdmin = User::factory()->admin()->create(['username' => 'admin-other-branch']);

        $preview = $this->service->validate($this->csv([$this->validRow()]), $this->branchId);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->patchRow($preview->previewId, 0, ['nama' => 'X'], $otherBranchAdmin->branch_id);
    }
}
