<?php

namespace Tests\Feature;

use App\Enum\Permission as PermissionEnum;
use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PermissionEndpoint;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KwitansiBulkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $permission = Permission::firstOrCreate([
            'name' => PermissionEnum::PRINT_KWITANSI->value,
            'guard_name' => 'web',
        ]);

        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'pembayaran.kwitansi'],
            ['permission_id' => $permission->id, 'is_active' => true],
        );
    }

    /** @var array<int, array{kelas: Kelas, kategori: Kategori, tahunAjaran: TahunAjaran, jenisTagihan: JenisTagihan}> */
    private array $sharedPerBranch = [];

    /**
     * Kelas/kategori/tahun ajaran/jenis tagihan dibuat sekali per branch —
     * membuatnya berulang kali melanggar unique constraint per branch.
     */
    private function sharedFor(Branch $branch): array
    {
        if (! isset($this->sharedPerBranch[$branch->id])) {
            $tahunAjaran = TahunAjaran::factory()->create(['branch_id' => $branch->id]);

            $this->sharedPerBranch[$branch->id] = [
                'kelas' => Kelas::factory()->create(['branch_id' => $branch->id]),
                'kategori' => Kategori::factory()->create(['branch_id' => $branch->id]),
                'tahunAjaran' => $tahunAjaran,
                'jenisTagihan' => JenisTagihan::factory()->create([
                    'nama' => 'SPP',
                    'jumlah' => 100000,
                    'branch_id' => $branch->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ]),
            ];
        }

        return $this->sharedPerBranch[$branch->id];
    }

    /**
     * Bikin satu pembayaran lengkap dengan relasinya.
     *
     * @return array{pembayaran: Pembayaran, siswa: Siswa, tagihan: Tagihan}
     */
    private function createPembayaran(Branch $branch, array $siswaAttributes = [], array $pembayaranAttributes = []): array
    {
        ['kelas' => $kelas, 'kategori' => $kategori, 'tahunAjaran' => $tahunAjaran, 'jenisTagihan' => $jt] = $this->sharedFor($branch);
        $wali = Wali::factory()->create();

        $siswa = Siswa::factory()
            ->for($wali, 'ayah')
            ->for($wali, 'ibu')
            ->for($wali, 'wali')
            ->for($kelas, 'kelas')
            ->for($kategori, 'kategori')
            ->create(array_merge([
                'jenjang' => 'MI',
                'branch_id' => $branch->id,
            ], $siswaAttributes));

        $tagihan = Tagihan::factory()
            ->for($siswa, 'siswa')
            ->for($jt, 'jenis_tagihan')
            ->create([
                'branch_id' => $branch->id,
                'tahun_ajaran_id' => $tahunAjaran->id,
            ]);

        $pembayaran = Pembayaran::factory()
            ->for($tagihan, 'tagihan')
            ->create(array_merge([
                'branch_id' => $branch->id,
            ], $pembayaranAttributes));

        return compact('pembayaran', 'siswa', 'tagihan');
    }

    private function adminFor(Branch $branch): User
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin-'.uniqid(),
            'branch_id' => $branch->id,
        ]);
        $admin->givePermissionTo(PermissionEnum::PRINT_KWITANSI->value);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_menggabungkan_semua_kwitansi_menjadi_satu_pdf()
    {
        $branch = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $admin = $this->adminFor($branch);

        for ($i = 0; $i < 3; $i++) {
            $this->createPembayaran($branch);
        }

        $response = $this->get('api/pembayaran/kwitansi-bulk');

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        // Inti fitur: ketiga kwitansi tergabung jadi satu file tiga halaman.
        $this->assertStringContainsString('/Count 3', $pdf);
    }

    public function test_filter_metode_mempersempit_hasil()
    {
        $branch = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $admin = $this->adminFor($branch);

        $this->createPembayaran($branch, [], ['metode' => 'offline']);
        $this->createPembayaran($branch, [], ['metode' => 'offline']);

        $this->get('api/pembayaran/kwitansi-bulk?metode=offline')
            ->assertStatus(200);

        $this->get('api/pembayaran/kwitansi-bulk?metode=online_midtrans')
            ->assertStatus(422);
    }

    public function test_filter_tanpa_hasil_menolak_dengan_422()
    {
        $branch = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $admin = $this->adminFor($branch);

        $this->createPembayaran($branch, ['nama' => 'Budi Santoso']);

        $this->get('api/pembayaran/kwitansi-bulk?search=TidakAdaSiswaIni')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['message']]);
    }

    public function test_pembayaran_cabang_lain_tidak_ikut_tercetak()
    {
        $branch = Branch::factory()->create();
        $branchLain = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $admin = $this->adminFor($branch);

        // Seluruh pembayaran milik cabang lain -> tidak ada yang cocok.
        $this->createPembayaran($branchLain);

        $this->get('api/pembayaran/kwitansi-bulk')
            ->assertStatus(422);
    }

    public function test_batas_jumlah_kwitansi_per_unduhan()
    {
        $branch = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $admin = $this->adminFor($branch);

        config(['pdf.kwitansi_bulk_limit' => 2]);

        for ($i = 0; $i < 3; $i++) {
            $this->createPembayaran($branch);
        }

        $this->get('api/pembayaran/kwitansi-bulk')
            ->assertStatus(422);
    }

    public function test_tanpa_informasi_sekolah_menolak_dengan_404()
    {
        $branch = Branch::factory()->create();
        $admin = $this->adminFor($branch);

        $this->createPembayaran($branch);

        $this->get('api/pembayaran/kwitansi-bulk')
            ->assertStatus(404);
    }

    public function test_user_tanpa_permission_print_kwitansi_ditolak()
    {
        $branch = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create([
            'username' => 'user-'.uniqid(),
            'branch_id' => $branch->id,
        ]);
        Sanctum::actingAs($user);

        $this->createPembayaran($branch);

        $this->get('api/pembayaran/kwitansi-bulk')
            ->assertStatus(403);
    }
}
