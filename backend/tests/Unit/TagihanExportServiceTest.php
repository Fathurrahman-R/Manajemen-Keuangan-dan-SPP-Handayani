<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Kelas;
use App\Models\Kategori;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Services\ImportExport\TagihanExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private TagihanExportService $service;
    private Branch $branch;
    private TahunAjaran $tahunAjaran;
    private Kelas $kelas;
    private Kategori $kategori;
    private Wali $wali;
    private JenisTagihan $jenisTagihan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TagihanExportService();

        $this->branch = Branch::factory()->create();
        $this->tahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $this->branch->id]);
        $this->kelas = Kelas::factory()->create(['branch_id' => $this->branch->id, 'jenjang' => 'MI', 'level' => 1]);
        $this->kategori = Kategori::factory()->create(['branch_id' => $this->branch->id]);
        $this->wali = Wali::factory()->create();
        $this->jenisTagihan = JenisTagihan::factory()->create([
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
    }

    private function createSiswaWithTagihan(array $siswaOverrides = [], array $tagihanOverrides = []): array
    {
        $siswa = Siswa::factory()->create(array_merge([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
        ], $siswaOverrides));

        $tagihan = Tagihan::factory()->create(array_merge([
            'nis' => $siswa->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ], $tagihanOverrides));

        return compact('siswa', 'tagihan');
    }

    public function test_build_query_scopes_to_branch_id(): void
    {
        $this->createSiswaWithTagihan();

        // Create tagihan in another branch
        $branch2 = Branch::factory()->create();
        $tahunAjaran2 = TahunAjaran::factory()->aktif()->create(['branch_id' => $branch2->id]);
        $kelas2 = Kelas::factory()->create(['branch_id' => $branch2->id]);
        $siswa2 = Siswa::factory()->create([
            'branch_id' => $branch2->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $kelas2->id,
            'kategori_id' => $this->kategori->id,
        ]);
        $jt2 = JenisTagihan::factory()->create(['branch_id' => $branch2->id, 'tahun_ajaran_id' => $tahunAjaran2->id]);
        Tagihan::factory()->create([
            'nis' => $siswa2->nis,
            'jenis_tagihan_id' => $jt2->id,
            'branch_id' => $branch2->id,
            'tahun_ajaran_id' => $tahunAjaran2->id,
        ]);

        $count = $this->service->getRecordCount([], $this->branch->id);
        $this->assertEquals(1, $count);
    }

    public function test_build_query_defaults_to_periode_aktif(): void
    {
        $this->createSiswaWithTagihan();

        // Tagihan on non-aktif period
        $tahunNonAktif = TahunAjaran::factory()->create(['branch_id' => $this->branch->id, 'status' => 'Non-Aktif']);
        $siswa2 = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
        ]);
        Tagihan::factory()->create([
            'nis' => $siswa2->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $tahunNonAktif->id,
        ]);

        // No tahun_ajaran_id filter → defaults to Periode_Aktif
        $count = $this->service->getRecordCount([], $this->branch->id);
        $this->assertEquals(1, $count);
    }

    public function test_build_query_filters_by_tahun_ajaran_id(): void
    {
        $this->createSiswaWithTagihan();

        $tahunNonAktif = TahunAjaran::factory()->create(['branch_id' => $this->branch->id, 'status' => 'Non-Aktif']);
        $siswa2 = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
        ]);
        Tagihan::factory()->create([
            'nis' => $siswa2->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $tahunNonAktif->id,
        ]);

        // Explicitly filter by non-aktif tahun
        $count = $this->service->getRecordCount(['tahun_ajaran_id' => $tahunNonAktif->id], $this->branch->id);
        $this->assertEquals(1, $count);
    }

    public function test_build_query_filters_by_status(): void
    {
        $this->createSiswaWithTagihan(['nis' => '1001'], ['status' => 'Lunas']);
        $this->createSiswaWithTagihan(['nis' => '1002'], ['status' => 'Belum Lunas']);

        $count = $this->service->getRecordCount(['status' => 'Lunas'], $this->branch->id);
        $this->assertEquals(1, $count);
    }

    public function test_build_query_filters_by_jenjang(): void
    {
        $this->createSiswaWithTagihan();

        // Create TK siswa with tagihan
        $kelasTK = Kelas::factory()->create(['branch_id' => $this->branch->id, 'jenjang' => 'TK']);
        $siswaTK = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'TK',
            'wali_id' => $this->wali->id,
            'kelas_id' => $kelasTK->id,
            'kategori_id' => $this->kategori->id,
        ]);
        Tagihan::factory()->create([
            'nis' => $siswaTK->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);

        $count = $this->service->getRecordCount(['jenjang' => 'MI'], $this->branch->id);
        $this->assertEquals(1, $count);
    }

    public function test_build_query_filters_by_kelas_id(): void
    {
        $kelas2 = Kelas::factory()->create(['branch_id' => $this->branch->id, 'jenjang' => 'MI', 'nama' => 'Kelas 2', 'level' => 2]);

        $data1 = $this->createSiswaWithTagihan();
        SiswaKelas::create([
            'siswa_id' => $data1['siswa']->id,
            'kelas_id' => $this->kelas->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);

        $siswa2 = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $kelas2->id,
            'kategori_id' => $this->kategori->id,
        ]);
        Tagihan::factory()->create([
            'nis' => $siswa2->nis,
            'jenis_tagihan_id' => $this->jenisTagihan->id,
            'branch_id' => $this->branch->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
        SiswaKelas::create([
            'siswa_id' => $siswa2->id,
            'kelas_id' => $kelas2->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);

        $count = $this->service->getRecordCount(['kelas_id' => $this->kelas->id], $this->branch->id);
        $this->assertEquals(1, $count);
    }

    public function test_export_dispatches_queue_when_count_exceeds_threshold(): void
    {
        // Use a partial mock to avoid creating 1001 records
        $service = $this->getMockBuilder(TagihanExportService::class)
            ->onlyMethods(['getRecordCount'])
            ->getMock();

        $service->expects($this->once())
            ->method('getRecordCount')
            ->willReturn(1001);

        $user = User::factory()->admin()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($user);

        $result = $service->export([], 'xlsx', $this->branch->id);

        $this->assertIsArray($result);
        $this->assertTrue($result['queued']);
        $this->assertArrayHasKey('job_reference', $result);
        $this->assertArrayHasKey('export_job_id', $result);
    }

    public function test_get_record_count_returns_correct_count(): void
    {
        $siswa = Siswa::factory()->create([
            'branch_id' => $this->branch->id,
            'jenjang' => 'MI',
            'wali_id' => $this->wali->id,
            'kelas_id' => $this->kelas->id,
            'kategori_id' => $this->kategori->id,
        ]);

        // Create tagihans one by one to avoid kode_tagihan collision
        for ($i = 0; $i < 3; $i++) {
            Tagihan::factory()->create([
                'nis' => $siswa->nis,
                'jenis_tagihan_id' => $this->jenisTagihan->id,
                'branch_id' => $this->branch->id,
                'tahun_ajaran_id' => $this->tahunAjaran->id,
            ]);
        }

        $count = $this->service->getRecordCount([], $this->branch->id);
        $this->assertEquals(3, $count);
    }
}
