<?php

namespace Tests\Feature\ImportExport;

use App\Models\ImportBatch;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\ImportExport\TagihanImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TagihanImportTest extends TestCase
{
    private User $admin;

    private int $branchId;

    private TahunAjaran $periodeAktif;

    private TagihanImportService $service;

    private ?Kelas $kelas = null;

    private ?Kategori $kategori = null;

    /**
     * `import_batches` punya FK ke `users`, sedangkan TestCase::setUp() menghapus
     * tabel users. Batch sisa test sebelumnya membuat penghapusan itu gagal, jadi
     * bersihkan di sini — sebelum parent setUp berikutnya berjalan.
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
        $this->service = app(TagihanImportService::class);

        $this->periodeAktif = TahunAjaran::factory()->aktif()->create([
            'branch_id' => $this->branchId,
            'nama' => '2025/2026',
        ]);
    }

    private function siswa(string $nis): Siswa
    {
        // Kelas unik per jenjang+branch+level, jadi dibuat sekali lalu dipakai ulang.
        $this->kelas ??= Kelas::factory()->create([
            'branch_id' => $this->branchId,
            'jenjang' => 'MI',
            'level' => 1,
        ]);
        $this->kategori ??= Kategori::factory()->create(['branch_id' => $this->branchId]);

        return Siswa::factory()->create([
            'nis' => $nis,
            'branch_id' => $this->branchId,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
            'jenjang' => 'MI',
            'status' => 'Aktif',
        ]);
    }

    private function jenisTagihan(string $nama): JenisTagihan
    {
        return JenisTagihan::factory()->create([
            'nama' => $nama,
            'branch_id' => $this->branchId,
            'tahun_ajaran_id' => $this->periodeAktif->id,
        ]);
    }

    private function csv(string $isi): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, $isi);

        return new UploadedFile($path, 'tagihan.csv', 'text/csv', null, true);
    }

    /**
     * Regresi: `tagihans` dan `jenis_tagihans` sama-sama punya kolom branch_id
     * dan tahun_ajaran_id. Tanpa prefiks tabel, MariaDB menolak query dengan
     * "Column 'branch_id' in WHERE is ambiguous" dan SELURUH import gagal —
     * bukan cuma baris duplikatnya.
     */
    public function test_validate_works_when_tagihan_already_exists_in_active_period(): void
    {
        $siswa = $this->siswa('000001');
        $jenis = $this->jenisTagihan('Seragam');

        Tagihan::create([
            'kode_tagihan' => 'TAG-TEST-0001',
            'jenis_tagihan_id' => $jenis->id,
            'nis' => $siswa->nis,
            'tmp' => 0,
            'status' => 'Belum Lunas',
            'branch_id' => $this->branchId,
            'tahun_ajaran_id' => $this->periodeAktif->id,
        ]);

        $this->siswa('000002');

        $preview = $this->service->validate(
            $this->csv("nis,jenis_tagihan\n000002,Seragam\n"),
            $this->branchId
        );

        $this->assertSame(1, $preview->totalRows);
        $this->assertSame(1, $preview->validRows);
        $this->assertSame(0, $preview->errorRows);
    }

    public function test_confirm_creates_unpaid_tagihan(): void
    {
        $this->siswa('000001');
        $this->jenisTagihan('Seragam');

        $preview = $this->service->validate(
            $this->csv("nis,jenis_tagihan\n000001,Seragam\n"),
            $this->branchId
        );

        $batch = $this->service->confirm($preview->previewId, $this->branchId, $this->admin->id);

        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->success_count);
        $this->assertDatabaseHas('tagihans', [
            'nis' => '000001',
            'status' => 'Belum Lunas',
            'batch_reference' => $batch->batch_reference,
        ]);
    }

    public function test_validate_flags_unknown_nis_and_jenis_tagihan(): void
    {
        $this->siswa('000001');
        $this->jenisTagihan('Seragam');

        $preview = $this->service->validate(
            $this->csv("nis,jenis_tagihan\n999999,Seragam\n000001,Tidak Ada\n"),
            $this->branchId
        );

        $this->assertSame(0, $preview->validRows);
        $this->assertSame(2, $preview->errorRows);

        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString("Siswa dengan NIS '999999' tidak ditemukan", $pesan);
        $this->assertStringContainsString("Jenis tagihan 'Tidak Ada' tidak ditemukan", $pesan);
    }

    public function test_validate_flags_duplicate_against_database_and_within_file(): void
    {
        $siswa = $this->siswa('000001');
        $jenis = $this->jenisTagihan('Seragam');
        $this->siswa('000002');

        Tagihan::create([
            'kode_tagihan' => 'TAG-TEST-0002',
            'jenis_tagihan_id' => $jenis->id,
            'nis' => $siswa->nis,
            'tmp' => 0,
            'status' => 'Belum Lunas',
            'branch_id' => $this->branchId,
            'tahun_ajaran_id' => $this->periodeAktif->id,
        ]);

        $preview = $this->service->validate(
            $this->csv("nis,jenis_tagihan\n000001,Seragam\n000002,Seragam\n000002,Seragam\n"),
            $this->branchId
        );

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(2, $preview->errorRows);

        $pesan = implode(' ', array_column($preview->errors, 'message'));
        $this->assertStringContainsString('sudah ada', $pesan);
        $this->assertStringContainsString('Duplikat dalam file', $pesan);
    }

    /**
     * Laporan per-baris (invalidRows) harus menyertakan identitas NIS +
     * jenis tagihan, bukan cuma nomor baris — requirement dosen penguji.
     */
    public function test_validate_reports_invalid_rows_with_identity(): void
    {
        $this->siswa('000001');
        $this->jenisTagihan('Seragam');

        $preview = $this->service->validate(
            $this->csv("nis,jenis_tagihan\n999999,Seragam\n"),
            $this->branchId
        );

        $this->assertCount(1, $preview->invalidRows);
        $this->assertSame(2, $preview->invalidRows[0]['row']);
        $this->assertSame(['nis' => '999999', 'jenis_tagihan' => 'Seragam'], $preview->invalidRows[0]['identity']);
        $this->assertStringContainsString('999999', $preview->summary);
        $this->assertStringContainsString('1 dari 1 baris tidak valid', $preview->summary);
    }

    /**
     * "Periode aktif belum diatur" sekarang jadi error tingkat-berkas
     * (dilempar dari validate()), bukan diulang di setiap baris seperti
     * sebelumnya — pesannya jadi jelas dan tidak menutupi error baris lain.
     */
    public function test_validate_throws_when_no_active_period(): void
    {
        $branchTanpaPeriode = User::factory()->admin()->create(['username' => 'admin-tanpa-periode'])->branch_id;
        Siswa::factory()->create(['nis' => '000001', 'branch_id' => $branchTanpaPeriode, 'kelas_id' => null, 'kategori_id' => null]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Periode aktif belum diatur untuk cabang ini.');

        $this->service->validate(
            $this->csv("nis,jenis_tagihan\n000001,Seragam\n"),
            $branchTanpaPeriode
        );
    }

    public function test_rollback_removes_imported_tagihan(): void
    {
        $this->siswa('000001');
        $this->jenisTagihan('Seragam');

        $preview = $this->service->validate(
            $this->csv("nis,jenis_tagihan\n000001,Seragam\n"),
            $this->branchId
        );
        $batch = $this->service->confirm($preview->previewId, $this->branchId, $this->admin->id);

        app(\App\Services\ImportExport\ImportBatchService::class)
            ->rollback($batch->batch_reference, $this->branchId, $this->admin->id);

        $this->assertDatabaseMissing('tagihans', ['batch_reference' => $batch->batch_reference]);
        $this->assertSame('rolled_back', ImportBatch::where('batch_reference', $batch->batch_reference)->value('status'));
    }
}
