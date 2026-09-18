<?php

namespace Tests\Feature\ImportExport;

use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\ImportExport\SiswaImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiswaImportValidationTest extends TestCase
{
    private const HEADER = 'nis,nisn,nama,jenis_kelamin,tempat_lahir,tanggal_lahir,agama,alamat,jenjang,kelas,kategori,status,keterangan_siswa';

    private User $admin;

    private int $branchId;

    private SiswaImportService $service;

    private Kelas $kelas;

    private Kategori $kategori;

    /**
     * `import_batches` punya FK ke `users`, sedangkan TestCase::setUp() menghapus
     * tabel users. Batch sisa test sebelumnya membuat penghapusan itu gagal, jadi
     * bersihkan di sini — sebelum parent setUp berikutnya berjalan (pola sama
     * dengan TagihanImportTest).
     */
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

        $this->kelas = Kelas::factory()->create([
            'branch_id' => $this->branchId,
            'jenjang' => 'MI',
            'level' => 1,
        ]);
        $this->kategori = Kategori::factory()->create(['branch_id' => $this->branchId]);
    }

    /**
     * @param  array<string, string>  $overrides
     */
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

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function csv(array $rows): UploadedFile
    {
        $lines = array_map(fn (array $row): string => implode(',', $row), $rows);
        $content = self::HEADER."\n".implode("\n", $lines)."\n";

        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'siswa.csv', 'text/csv', null, true);
    }

    public function test_rejects_nis_already_used_in_another_branch(): void
    {
        $otherBranchAdmin = User::factory()->admin()->create(['username' => 'admin-other-branch-1']);
        Siswa::factory()->create([
            'nis' => '000101',
            'branch_id' => $otherBranchAdmin->branch_id,
            'kelas_id' => null,
            'kategori_id' => null,
        ]);

        $preview = $this->service->validate(
            $this->csv([$this->validRow(['nis' => '000101'])]),
            $this->branchId
        );

        $this->assertSame(0, $preview->validRows);
        $this->assertSame(1, $preview->errorRows);
        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString('NIS sudah terdaftar di sistem', $pesan);
    }

    public function test_rejects_nisn_already_used_in_database(): void
    {
        $otherBranchAdmin = User::factory()->admin()->create(['username' => 'admin-other-branch-2']);
        Siswa::factory()->create([
            'nisn' => '1234567890',
            'branch_id' => $otherBranchAdmin->branch_id,
            'kelas_id' => null,
            'kategori_id' => null,
        ]);

        $preview = $this->service->validate(
            $this->csv([$this->validRow(['nisn' => '1234567890'])]),
            $this->branchId
        );

        $this->assertSame(0, $preview->validRows);
        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString("NISN '1234567890' sudah terdaftar di sistem", $pesan);
    }

    public function test_rejects_nisn_duplicated_within_file(): void
    {
        $preview = $this->service->validate(
            $this->csv([
                $this->validRow(['nis' => '000101', 'nisn' => '1234567890']),
                $this->validRow(['nis' => '000102', 'nisn' => '1234567890']),
            ]),
            $this->branchId
        );

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(1, $preview->errorRows);
        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString('NISN duplikat dalam file', $pesan);
    }

    public function test_rejects_empty_alamat_and_tempat_lahir(): void
    {
        $preview = $this->service->validate(
            $this->csv([$this->validRow(['alamat' => '', 'tempat_lahir' => ''])]),
            $this->branchId
        );

        $this->assertSame(0, $preview->validRows);
        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString('Alamat wajib diisi', $pesan);
        $this->assertStringContainsString('Tempat lahir wajib diisi', $pesan);
    }

    public function test_rejects_status_not_in_enum(): void
    {
        $preview = $this->service->validate(
            $this->csv([$this->validRow(['status' => 'Non-Aktif'])]),
            $this->branchId
        );

        $this->assertSame(0, $preview->validRows);
        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString('Status harus salah satu dari', $pesan);
    }

    /**
     * Regresi: sebelumnya NIS hanya didaftarkan ke pelacak duplikat-dalam-file
     * kalau baris itu sendiri lolos validasi. Baris 1 di sini error karena
     * alamat kosong (bukan karena NIS-nya), jadi NIS-nya harus tetap
     * terdaftar dan baris 2 dengan NIS sama harus tetap ditandai duplikat.
     */
    public function test_detects_duplicate_nis_even_when_first_occurrence_itself_errored(): void
    {
        $preview = $this->service->validate(
            $this->csv([
                $this->validRow(['nis' => '000101', 'nisn' => '1111111111', 'alamat' => '']),
                $this->validRow(['nis' => '000101', 'nisn' => '2222222222']),
            ]),
            $this->branchId
        );

        $this->assertSame(0, $preview->validRows);
        $this->assertSame(2, $preview->errorRows);
        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString('NIS duplikat dalam file', $pesan);
    }

    public function test_valid_row_passes_with_no_errors(): void
    {
        $preview = $this->service->validate(
            $this->csv([$this->validRow()]),
            $this->branchId
        );

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(0, $preview->errorRows);
        $this->assertSame([], $preview->invalidRows);
        $this->assertSame('', $preview->summary);
    }

    public function test_invalid_rows_report_includes_row_number_and_nis(): void
    {
        $preview = $this->service->validate(
            $this->csv([$this->validRow(['agama' => 'Hindhu'])]),
            $this->branchId
        );

        $this->assertCount(1, $preview->invalidRows);
        $this->assertSame(2, $preview->invalidRows[0]['row']);
        $this->assertSame('000101', $preview->invalidRows[0]['identity']['nis']);
        $this->assertStringContainsString('Baris 2 (NIS 000101', $preview->invalidRows[0]['label']);
        $this->assertStringContainsString('1 dari 1 baris tidak valid', $preview->summary);
    }
}
