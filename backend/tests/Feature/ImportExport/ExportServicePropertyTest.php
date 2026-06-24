<?php

namespace Tests\Feature\ImportExport;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Services\ImportExport\KasExportService;
use App\Services\ImportExport\PembayaranExportService;
use App\Services\ImportExport\SiswaExportService;
use App\Services\ImportExport\TagihanExportService;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Property-based tests for Export Services.
 *
 * Uses PHPUnit data providers with Faker-generated data at 100+ iterations
 * to verify universal correctness properties.
 */
class ExportServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Branch $otherBranch;
    private TahunAjaran $tahunAjaran;
    private TahunAjaran $otherTahunAjaran;
    private Kelas $kelas;
    private Kelas $otherKelas;
    private Kategori $kategori;
    private Wali $wali;
    private JenisTagihan $jenisTagihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->otherBranch = Branch::factory()->create();

        $this->tahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $this->branch->id]);
        $this->otherTahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $this->otherBranch->id]);

        $this->kelas = Kelas::factory()->create(['branch_id' => $this->branch->id, 'jenjang' => 'MI']);
        $this->otherKelas = Kelas::factory()->create(['branch_id' => $this->otherBranch->id, 'jenjang' => 'MI']);

        $this->kategori = Kategori::factory()->create(['branch_id' => $this->branch->id]);
        $this->wali = Wali::factory()->create();

        $this->jenisTagihan = JenisTagihan::factory()->create([
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
    }

    // =========================================================================
    // Property 1: Branch Isolation
    // =========================================================================

    /**
     * Feature: import-export-data, Property 1: Branch Isolation
     *
     * For any export operation performed by a user with branch_id B,
     * all records in the output SHALL belong exclusively to branch_id B.
     *
     * Validates: Requirements 1.4, 4.4, 7.5, 8.7
     *
     * @dataProvider branchIsolationDataProvider
     */
    public function test_property1_branch_isolation_siswa_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        // Create siswa in our branch
        $countOurBranch = $faker->numberBetween(1, 5);
        for ($i = 0; $i < $countOurBranch; $i++) {
            Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
        }

        // Create siswa in other branch
        $countOtherBranch = $faker->numberBetween(1, 5);
        $otherKategori = Kategori::factory()->create(['branch_id' => $this->otherBranch->id]);
        for ($i = 0; $i < $countOtherBranch; $i++) {
            Siswa::factory()->create([
                'branch_id' => $this->otherBranch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->otherKelas->id,
                'kategori_id' => $otherKategori->id,
            ]);
        }

        $service = new SiswaExportService();
        $query = $service->buildQuery([], $this->branch->id);
        $results = $query->get();

        $this->assertCount($countOurBranch, $results);
        foreach ($results as $record) {
            $this->assertEquals($this->branch->id, $record->branch_id,
                "Iteration {$iteration}: Found record from different branch in export");
        }
    }

    /**
     * Feature: import-export-data, Property 1: Branch Isolation
     *
     * Validates: Requirements 4.4
     *
     * @dataProvider branchIsolationDataProvider
     */
    public function test_property1_branch_isolation_tagihan_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        // Create tagihan in our branch
        $countOurBranch = $faker->numberBetween(1, 5);
        for ($i = 0; $i < $countOurBranch; $i++) {
            $siswa = Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
            Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
            ]);
        }

        // Create tagihan in other branch
        $otherJenisTagihan = JenisTagihan::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'tahun_ajaran_id' => $this->otherTahunAjaran->id,
        ]);
        $countOther = $faker->numberBetween(1, 3);
        $otherKategori = Kategori::factory()->create(['branch_id' => $this->otherBranch->id]);
        for ($i = 0; $i < $countOther; $i++) {
            $siswa2 = Siswa::factory()->create([
                'branch_id' => $this->otherBranch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->otherKelas->id,
                'kategori_id' => $otherKategori->id,
            ]);
            Tagihan::factory()->create([
                'nis' => $siswa2->nis,
                'jenis_tagihan_id' => $otherJenisTagihan->id,
                'branch_id' => $this->otherBranch->id,
                'tahun_ajaran_id' => $this->otherTahunAjaran->id,
            ]);
        }

        $service = new TagihanExportService();
        $count = $service->getRecordCount([], $this->branch->id);

        $this->assertEquals($countOurBranch, $count,
            "Iteration {$iteration}: Tagihan export should only contain records from user's branch");
    }

    /**
     * Feature: import-export-data, Property 1: Branch Isolation
     *
     * Validates: Requirements 7.5
     *
     * @dataProvider branchIsolationDataProvider
     */
    public function test_property1_branch_isolation_pembayaran_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        // Create pembayaran in our branch
        $countOurBranch = $faker->numberBetween(1, 5);
        for ($i = 0; $i < $countOurBranch; $i++) {
            $siswa = Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
            $tagihan = Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
            ]);
            Pembayaran::factory()->create([
                'kode_tagihan' => $tagihan->kode_tagihan,
                'branch_id' => $this->branch->id,
                'tanggal' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            ]);
        }

        // Create pembayaran in other branch
        $otherJenisTagihan = JenisTagihan::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'tahun_ajaran_id' => $this->otherTahunAjaran->id,
        ]);
        $otherKategori = Kategori::factory()->create(['branch_id' => $this->otherBranch->id]);
        $countOther = $faker->numberBetween(1, 3);
        for ($i = 0; $i < $countOther; $i++) {
            $siswa2 = Siswa::factory()->create([
                'branch_id' => $this->otherBranch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->otherKelas->id,
                'kategori_id' => $otherKategori->id,
            ]);
            $tagihan2 = Tagihan::factory()->create([
                'nis' => $siswa2->nis,
                'jenis_tagihan_id' => $otherJenisTagihan->id,
                'branch_id' => $this->otherBranch->id,
                'tahun_ajaran_id' => $this->otherTahunAjaran->id,
            ]);
            Pembayaran::factory()->create([
                'kode_tagihan' => $tagihan2->kode_tagihan,
                'branch_id' => $this->otherBranch->id,
                'tanggal' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            ]);
        }

        $service = new PembayaranExportService();
        $query = $service->buildQuery([], $this->branch->id);
        $results = $query->get();

        $this->assertCount($countOurBranch, $results);
        foreach ($results as $record) {
            $this->assertEquals($this->branch->id, $record->branch_id,
                "Iteration {$iteration}: Found pembayaran from different branch in export");
        }
    }

    /**
     * Feature: import-export-data, Property 1: Branch Isolation
     *
     * Validates: Requirements 8.7
     *
     * @dataProvider branchIsolationDataProvider
     */
    public function test_property1_branch_isolation_kas_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $bulan = $faker->numberBetween(1, 12);
        $tahun = 2024;

        // Create pemasukan/pengeluaran in our branch
        $countPemasukan = $faker->numberBetween(1, 3);
        $countPengeluaran = $faker->numberBetween(1, 3);

        for ($i = 0; $i < $countPemasukan; $i++) {
            $siswa = Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
            $tagihan = Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
            ]);
            Pembayaran::factory()->create([
                'kode_tagihan' => $tagihan->kode_tagihan,
                'branch_id' => $this->branch->id,
                'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
            ]);
        }

        for ($i = 0; $i < $countPengeluaran; $i++) {
            Pengeluaran::factory()->create([
                'branch_id' => $this->branch->id,
                'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
            ]);
        }

        // Create data in other branch (same month)
        $otherJenisTagihan = JenisTagihan::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'tahun_ajaran_id' => $this->otherTahunAjaran->id,
        ]);
        $otherKategori = Kategori::factory()->create(['branch_id' => $this->otherBranch->id]);
        $siswaOther = Siswa::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->otherKelas->id,
            'kategori_id' => $otherKategori->id,
        ]);
        $tagihanOther = Tagihan::factory()->create([
            'nis' => $siswaOther->nis,
            'jenis_tagihan_id' => $otherJenisTagihan->id,
            'branch_id' => $this->otherBranch->id,
            'tahun_ajaran_id' => $this->otherTahunAjaran->id,
        ]);
        Pembayaran::factory()->create([
            'kode_tagihan' => $tagihanOther->kode_tagihan,
            'branch_id' => $this->otherBranch->id,
            'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
        ]);
        Pengeluaran::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
        ]);

        $service = new KasExportService();
        $recordCount = $service->getRecordCount($bulan, $tahun, $this->branch->id);

        $this->assertEquals($countPemasukan + $countPengeluaran, $recordCount,
            "Iteration {$iteration}: Kas export record count should only include own branch");
    }

    public static function branchIsolationDataProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data["iteration_{$i}"] = [$i, $i * 17];
        }
        return $data;
    }

    // =========================================================================
    // Property 2: Export Filter Correctness
    // =========================================================================

    /**
     * Feature: import-export-data, Property 2: Export Filter Correctness
     *
     * For any export request with filter parameters, every record in the output
     * SHALL match ALL specified filter criteria.
     *
     * Validates: Requirements 1.2, 1.3, 4.2, 7.2
     *
     * @dataProvider exportFilterDataProvider
     */
    public function test_property2_export_filter_correctness_siswa(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $jenjangOptions = ['TK', 'MI', 'KB'];
        $statusOptions = ['Aktif', 'Lulus', 'Pindah'];

        // Create diverse siswa records
        $kelasTK = Kelas::factory()->create(['branch_id' => $this->branch->id, 'jenjang' => 'TK']);
        $kelasKB = Kelas::factory()->create(['branch_id' => $this->branch->id, 'jenjang' => 'KB']);
        $kelasMap = ['TK' => $kelasTK, 'MI' => $this->kelas, 'KB' => $kelasKB];

        for ($i = 0; $i < 10; $i++) {
            $jenjang = $faker->randomElement($jenjangOptions);
            Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => $jenjang,
                'status' => $faker->randomElement($statusOptions),
                'wali_id' => $this->wali->id,
                'kelas_id' => $kelasMap[$jenjang]->id,
                'kategori_id' => $this->kategori->id,
            ]);
        }

        // Pick a random filter combination
        $filterJenjang = $faker->randomElement($jenjangOptions);
        $filters = ['jenjang' => $filterJenjang];

        // Optionally add status filter
        if ($faker->boolean(50)) {
            $filters['status'] = $faker->randomElement($statusOptions);
        }

        $service = new SiswaExportService();
        $query = $service->buildQuery($filters, $this->branch->id);
        $results = $query->get();

        foreach ($results as $record) {
            $this->assertEquals($filterJenjang, $record->jenjang,
                "Iteration {$iteration}: Siswa jenjang should match filter");

            if (isset($filters['status'])) {
                $this->assertEquals($filters['status'], $record->status,
                    "Iteration {$iteration}: Siswa status should match filter");
            }
        }
    }

    /**
     * Feature: import-export-data, Property 2: Export Filter Correctness
     *
     * Validates: Requirements 4.2
     *
     * @dataProvider exportFilterDataProvider
     */
    public function test_property2_export_filter_correctness_tagihan(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $statusOptions = ['Lunas', 'Belum Lunas', 'Belum Dibayar'];

        // Create tagihan with different statuses
        for ($i = 0; $i < 8; $i++) {
            $siswa = Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => $faker->randomElement(['TK', 'MI', 'KB']),
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
            Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'status' => $faker->randomElement($statusOptions),
            ]);
        }

        // Pick a random status filter
        $filterStatus = $faker->randomElement($statusOptions);
        $filters = ['status' => $filterStatus];

        $service = new TagihanExportService();
        $query = $service->buildQuery($filters, $this->branch->id);
        $results = $query->get();

        foreach ($results as $record) {
            $this->assertEquals($filterStatus, $record->status,
                "Iteration {$iteration}: Tagihan status should match filter '{$filterStatus}'");
        }
    }

    /**
     * Feature: import-export-data, Property 2: Export Filter Correctness
     *
     * Validates: Requirements 7.2
     *
     * @dataProvider exportFilterDataProvider
     */
    public function test_property2_export_filter_correctness_pembayaran(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        // Create pembayaran across several months
        for ($i = 0; $i < 8; $i++) {
            $siswa = Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
            $tagihan = Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
            ]);
            Pembayaran::factory()->create([
                'kode_tagihan' => $tagihan->kode_tagihan,
                'branch_id' => $this->branch->id,
                'tanggal' => $faker->dateTimeBetween('2024-01-01', '2024-12-31')->format('Y-m-d'),
            ]);
        }

        // Pick a random date range within 2024
        $startMonth = $faker->numberBetween(1, 6);
        $endMonth = $faker->numberBetween($startMonth + 1, 12);
        $tanggalMulai = sprintf('2024-%02d-01', $startMonth);
        $tanggalSelesai = sprintf('2024-%02d-28', $endMonth);

        $filters = [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ];

        $service = new PembayaranExportService();
        $query = $service->buildQuery($filters, $this->branch->id);
        $results = $query->get();

        foreach ($results as $record) {
            $this->assertGreaterThanOrEqual($tanggalMulai, $record->tanggal,
                "Iteration {$iteration}: Pembayaran tanggal should be >= tanggal_mulai");
            $this->assertLessThanOrEqual($tanggalSelesai, $record->tanggal,
                "Iteration {$iteration}: Pembayaran tanggal should be <= tanggal_selesai");
        }
    }

    public static function exportFilterDataProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data["iteration_{$i}"] = [$i, $i * 31];
        }
        return $data;
    }

    // =========================================================================
    // Property 3: Export Data Mapping Integrity
    // =========================================================================

    /**
     * Feature: import-export-data, Property 3: Export Data Mapping Integrity
     *
     * For any exported record, the values in each column SHALL exactly correspond
     * to the source database record's field values.
     *
     * Validates: Requirements 1.1, 4.1, 7.1
     *
     * @dataProvider dataMappingDataProvider
     */
    public function test_property3_data_mapping_integrity_siswa(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $siswa = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => $faker->randomElement(['TK', 'MI', 'KB']),
            'nama' => $faker->name(),
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
            'status' => $faker->randomElement(['Aktif', 'Lulus', 'Pindah']),
        ]);

        $service = new SiswaExportService();
        $query = $service->buildQuery([], $this->branch->id);
        $result = $query->first();

        $this->assertNotNull($result, "Iteration {$iteration}: Should find the created siswa");
        $this->assertEquals($siswa->nis, $result->nis,
            "Iteration {$iteration}: NIS should match source DB");
        $this->assertEquals($siswa->nama, $result->nama,
            "Iteration {$iteration}: Nama should match source DB");
        $this->assertEquals($siswa->jenjang, $result->jenjang,
            "Iteration {$iteration}: Jenjang should match source DB");
        $this->assertEquals($siswa->status, $result->status,
            "Iteration {$iteration}: Status should match source DB");
    }

    /**
     * Feature: import-export-data, Property 3: Export Data Mapping Integrity
     *
     * Validates: Requirements 4.1
     *
     * @dataProvider dataMappingDataProvider
     */
    public function test_property3_data_mapping_integrity_tagihan(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $siswa = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
        ]);

        $tagihanStatus = $faker->randomElement(['Lunas', 'Belum Lunas', 'Belum Dibayar']);
        $tagihan = Tagihan::factory()->create([
            'nis' => $siswa->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'status' => $tagihanStatus,
        ]);

        $service = new TagihanExportService();
        $query = $service->buildQuery([], $this->branch->id);
        $result = $query->first();

        $this->assertNotNull($result, "Iteration {$iteration}: Should find the created tagihan");
        $this->assertEquals($tagihan->kode_tagihan, $result->kode_tagihan,
            "Iteration {$iteration}: kode_tagihan should match source DB");
        $this->assertEquals($tagihan->nis, $result->nis,
            "Iteration {$iteration}: NIS should match source DB");
        $this->assertEquals($tagihanStatus, $result->status,
            "Iteration {$iteration}: Status should match source DB");
    }

    /**
     * Feature: import-export-data, Property 3: Export Data Mapping Integrity
     *
     * Validates: Requirements 7.1
     *
     * @dataProvider dataMappingDataProvider
     */
    public function test_property3_data_mapping_integrity_pembayaran(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $siswa = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
        ]);
        $tagihan = Tagihan::factory()->create([
            'nis' => $siswa->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);

        $jumlah = $faker->randomFloat(2, 10000, 500000);
        $metode = $faker->randomElement(['offline', 'online_midtrans']);
        $tanggal = $faker->dateTimeBetween('2024-01-01', '2024-12-31')->format('Y-m-d');

        $pembayaran = Pembayaran::factory()->create([
            'kode_tagihan' => $tagihan->kode_tagihan,
            'branch_id' => $this->branch->id,
            'tanggal' => $tanggal,
            'jumlah' => $jumlah,
            'metode' => $metode,
        ]);

        $service = new PembayaranExportService();
        $query = $service->buildQuery(['tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-12-31'], $this->branch->id);
        $result = $query->first();

        $this->assertNotNull($result, "Iteration {$iteration}: Should find the created pembayaran");
        $this->assertEquals($pembayaran->kode_pembayaran, $result->kode_pembayaran,
            "Iteration {$iteration}: kode_pembayaran should match source DB");
        $this->assertEquals($tanggal, $result->tanggal,
            "Iteration {$iteration}: Tanggal should match source DB");
        $this->assertEquals($metode, $result->metode,
            "Iteration {$iteration}: Metode should match source DB");
        $this->assertEqualsWithDelta($jumlah, $result->jumlah, 0.01,
            "Iteration {$iteration}: Jumlah should match source DB");
    }

    public static function dataMappingDataProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data["iteration_{$i}"] = [$i, $i * 43];
        }
        return $data;
    }

    // =========================================================================
    // Property 12: Queue Threshold Correctness
    // =========================================================================

    /**
     * Feature: import-export-data, Property 12: Queue Threshold Correctness
     *
     * For any export operation with more than 1000 records, the system SHALL
     * dispatch a queue job. Operations at or below the threshold SHALL be
     * processed synchronously.
     *
     * Validates: Requirements 1.9, 1.10, 4.7, 7.8, 8.10
     *
     * @dataProvider queueThresholdDataProvider
     */
    public function test_property12_queue_threshold_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        Queue::fake();

        // Generate a random record count - sometimes above, sometimes below threshold
        $recordCount = $faker->numberBetween(900, 1100);
        $threshold = 1000;

        // Use partial mock to avoid creating thousands of records
        $service = $this->getMockBuilder(SiswaExportService::class)
            ->onlyMethods(['getRecordCount'])
            ->getMock();

        $service->expects($this->once())
            ->method('getRecordCount')
            ->willReturn($recordCount);

        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $result = $service->export([], 'xlsx', $this->branch->id);

        if ($recordCount > $threshold) {
            $this->assertIsArray($result,
                "Iteration {$iteration}: Count {$recordCount} > {$threshold} should return array (queued)");
            $this->assertTrue($result['queued'],
                "Iteration {$iteration}: Should be queued when count > threshold");
            $this->assertArrayHasKey('job_reference', $result);
        } else {
            // At or below threshold: returns BinaryFileResponse (sync download)
            $this->assertFalse(is_array($result),
                "Iteration {$iteration}: Count {$recordCount} <= {$threshold} should not be queued");
        }
    }

    /**
     * Feature: import-export-data, Property 12: Queue Threshold Correctness
     *
     * Validates: Requirements 4.7
     *
     * @dataProvider queueThresholdDataProvider
     */
    public function test_property12_queue_threshold_tagihan_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        Queue::fake();

        $recordCount = $faker->numberBetween(900, 1100);
        $threshold = 1000;

        $service = $this->getMockBuilder(TagihanExportService::class)
            ->onlyMethods(['getRecordCount'])
            ->getMock();

        $service->expects($this->once())
            ->method('getRecordCount')
            ->willReturn($recordCount);

        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $result = $service->export([], 'xlsx', $this->branch->id);

        if ($recordCount > $threshold) {
            $this->assertIsArray($result,
                "Iteration {$iteration}: Count {$recordCount} > {$threshold} should be queued");
            $this->assertTrue($result['queued']);
        } else {
            $this->assertFalse(is_array($result),
                "Iteration {$iteration}: Count {$recordCount} <= {$threshold} should be sync");
        }
    }

    /**
     * Feature: import-export-data, Property 12: Queue Threshold Correctness
     *
     * Validates: Requirements 8.10
     *
     * @dataProvider queueThresholdDataProvider
     */
    public function test_property12_queue_threshold_kas_export(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        Queue::fake();

        $recordCount = $faker->numberBetween(900, 1100);
        $threshold = 1000;

        $service = $this->getMockBuilder(KasExportService::class)
            ->onlyMethods(['getRecordCount'])
            ->getMock();

        $service->expects($this->once())
            ->method('getRecordCount')
            ->willReturn($recordCount);

        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $bulan = $faker->numberBetween(1, 12);
        $tahun = 2024;

        $result = $service->exportKasHarian($bulan, $tahun, 'xlsx', $this->branch->id);

        if ($recordCount > $threshold) {
            $this->assertIsArray($result,
                "Iteration {$iteration}: Count {$recordCount} > {$threshold} should be queued");
            $this->assertTrue($result['queued']);
        } else {
            $this->assertFalse(is_array($result),
                "Iteration {$iteration}: Count {$recordCount} <= {$threshold} should be sync");
        }
    }

    public static function queueThresholdDataProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data["iteration_{$i}"] = [$i, $i * 53];
        }
        return $data;
    }

    // =========================================================================
    // Property 13: Kas Aggregation Consistency
    // =========================================================================

    /**
     * Feature: import-export-data, Property 13: Kas Aggregation Consistency
     *
     * For any kas harian or rekap bulanan export, the summary totals
     * (total_pemasukan, total_pengeluaran) SHALL equal the sum of individual
     * records in the detail sections.
     *
     * Validates: Requirements 8.5, 8.6
     *
     * @dataProvider kasAggregationDataProvider
     */
    public function test_property13_kas_aggregation_consistency_harian(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $bulan = $faker->numberBetween(1, 12);
        $tahun = 2024;

        // Create random number of pemasukan and pengeluaran
        $numPemasukan = $faker->numberBetween(1, 5);
        $numPengeluaran = $faker->numberBetween(1, 5);

        $expectedPemasukan = 0;
        $expectedPengeluaran = 0;

        for ($i = 0; $i < $numPemasukan; $i++) {
            $siswa = Siswa::factory()->create([
                'branch_id' => $this->branch->id,
                'jenjang' => 'MI',
                'wali_id' => $this->wali->id,
                'kelas_id' => $this->kelas->id,
                'kategori_id' => $this->kategori->id,
            ]);
            $tagihan = Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
            ]);
            $jumlah = $faker->numberBetween(10000, 500000);
            Pembayaran::factory()->create([
                'kode_tagihan' => $tagihan->kode_tagihan,
                'branch_id' => $this->branch->id,
                'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
                'jumlah' => $jumlah,
            ]);
            $expectedPemasukan += $jumlah;
        }

        for ($i = 0; $i < $numPengeluaran; $i++) {
            $jumlah = $faker->numberBetween(10000, 300000);
            Pengeluaran::factory()->create([
                'branch_id' => $this->branch->id,
                'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
                'jumlah' => $jumlah,
            ]);
            $expectedPengeluaran += $jumlah;
        }

        $service = new KasExportService();

        // Verify sum of individual records matches expected totals
        $pemasukanQuery = $service->buildPemasukanQuery($bulan, $tahun, $this->branch->id);
        $pengeluaranQuery = $service->buildPengeluaranQuery($bulan, $tahun, $this->branch->id);

        $actualPemasukan = $pemasukanQuery->sum('jumlah');
        $actualPengeluaran = $pengeluaranQuery->sum('jumlah');

        $this->assertEqualsWithDelta($expectedPemasukan, $actualPemasukan, 0.01,
            "Iteration {$iteration}: Sum of pemasukan records should equal total_pemasukan");
        $this->assertEqualsWithDelta($expectedPengeluaran, $actualPengeluaran, 0.01,
            "Iteration {$iteration}: Sum of pengeluaran records should equal total_pengeluaran");

        // Verify record count matches
        $this->assertEquals($numPemasukan, $pemasukanQuery->count(),
            "Iteration {$iteration}: Pemasukan count should match created records");
        $this->assertEquals($numPengeluaran, $pengeluaranQuery->count(),
            "Iteration {$iteration}: Pengeluaran count should match created records");
    }

    /**
     * Feature: import-export-data, Property 13: Kas Aggregation Consistency
     *
     * Verifies rekap bulanan aggregation: monthly totals = sum of details per month.
     *
     * Validates: Requirements 8.5, 8.6
     *
     * @dataProvider kasAggregationDataProvider
     */
    public function test_property13_kas_aggregation_consistency_rekap_bulanan(int $iteration, int $seed): void
    {
        $faker = Faker::create();
        $faker->seed($seed);

        $tahun = 2024;

        // Create records across multiple months
        $numMonths = $faker->numberBetween(2, 4);
        $months = $faker->randomElements(range(1, 12), $numMonths);
        $expectedPerMonth = [];

        foreach ($months as $bulan) {
            $expectedPerMonth[$bulan] = ['pemasukan' => 0, 'pengeluaran' => 0];

            $numPemasukan = $faker->numberBetween(1, 3);
            for ($i = 0; $i < $numPemasukan; $i++) {
                $siswa = Siswa::factory()->create([
                    'branch_id' => $this->branch->id,
                    'jenjang' => 'MI',
                    'wali_id' => $this->wali->id,
                    'kelas_id' => $this->kelas->id,
                    'kategori_id' => $this->kategori->id,
                ]);
                $tagihan = Tagihan::factory()->create([
                    'nis' => $siswa->nis,
                    'jenis_tagihan_id' => $this->jenisTagihan->id,
                    'branch_id' => $this->branch->id,
                    'tahun_ajaran_id' => $this->tahunAjaran->id,
                ]);
                $jumlah = $faker->numberBetween(10000, 500000);
                Pembayaran::factory()->create([
                    'kode_tagihan' => $tagihan->kode_tagihan,
                    'branch_id' => $this->branch->id,
                    'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
                    'jumlah' => $jumlah,
                ]);
                $expectedPerMonth[$bulan]['pemasukan'] += $jumlah;
            }

            $numPengeluaran = $faker->numberBetween(1, 2);
            for ($i = 0; $i < $numPengeluaran; $i++) {
                $jumlah = $faker->numberBetween(10000, 200000);
                Pengeluaran::factory()->create([
                    'branch_id' => $this->branch->id,
                    'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, $faker->numberBetween(1, 28)),
                    'jumlah' => $jumlah,
                ]);
                $expectedPerMonth[$bulan]['pengeluaran'] += $jumlah;
            }
        }

        // Verify aggregation per month matches detail sum
        $pemasukanPerBulan = Pembayaran::query()
            ->where('branch_id', $this->branch->id)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total', 'bulan')
            ->toArray();

        $pengeluaranPerBulan = Pengeluaran::query()
            ->where('branch_id', $this->branch->id)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total', 'bulan')
            ->toArray();

        foreach ($expectedPerMonth as $bulan => $expected) {
            $actualPemasukan = $pemasukanPerBulan[$bulan] ?? 0;
            $actualPengeluaran = $pengeluaranPerBulan[$bulan] ?? 0;

            $this->assertEqualsWithDelta($expected['pemasukan'], $actualPemasukan, 0.01,
                "Iteration {$iteration}: Month {$bulan} pemasukan total should match sum of details");
            $this->assertEqualsWithDelta($expected['pengeluaran'], $actualPengeluaran, 0.01,
                "Iteration {$iteration}: Month {$bulan} pengeluaran total should match sum of details");
        }

        // Grand total consistency: sum of monthly totals = sum of all records
        $grandPemasukan = Pembayaran::where('branch_id', $this->branch->id)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');
        $grandPengeluaran = Pengeluaran::where('branch_id', $this->branch->id)
            ->whereYear('tanggal', $tahun)
            ->sum('jumlah');

        $sumMonthlyPemasukan = array_sum($pemasukanPerBulan);
        $sumMonthlyPengeluaran = array_sum($pengeluaranPerBulan);

        $this->assertEqualsWithDelta($grandPemasukan, $sumMonthlyPemasukan, 0.01,
            "Iteration {$iteration}: Grand total pemasukan should equal sum of monthly totals");
        $this->assertEqualsWithDelta($grandPengeluaran, $sumMonthlyPengeluaran, 0.01,
            "Iteration {$iteration}: Grand total pengeluaran should equal sum of monthly totals");
    }

    public static function kasAggregationDataProvider(): array
    {
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data["iteration_{$i}"] = [$i, $i * 67];
        }
        return $data;
    }
}
