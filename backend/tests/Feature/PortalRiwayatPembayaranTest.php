<?php

namespace Tests\Feature;

use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\MidtransTransaction;
use App\Models\Pembayaran;
use App\Models\PermissionEndpoint;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Endpoint di balik halaman Portal Siswa "Riwayat Pembayaran".
 * Fokus: transaksi Midtrans pending yang ikut jadi baris list harus ikut
 * terhitung di meta.total, bukan ditempel di luar paginator.
 */
class PortalRiwayatPembayaranTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MidtransTransaction::query()->delete();

        // EndpointPermission strict mode: tanpa mapping, request langsung 403.
        PermissionEndpoint::updateOrCreate(
            ['resource_key' => 'pembayaran.siswa'],
            ['permission_id' => null, 'is_active' => true],
        );
    }

    protected function tearDown(): void
    {
        MidtransTransaction::query()->delete();

        parent::tearDown();
    }

    /**
     * @return array{siswa: Siswa, tagihan: Tagihan, user: User}
     */
    private function siswaWithRiwayat(int $jumlahPembayaran, int $jumlahPending): array
    {
        $user = User::factory()->create();
        $branchId = $user->branch_id;

        $kelas = Kelas::factory()->create(['branch_id' => $branchId]);
        $kategori = Kategori::factory()->create(['branch_id' => $branchId]);
        $siswa = Siswa::factory()->create([
            'branch_id' => $branchId,
            'kelas_id' => $kelas->id,
            'kategori_id' => $kategori->id,
        ]);
        $tahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $branchId]);
        $jenisTagihan = JenisTagihan::factory()->create([
            'nama' => 'SPP',
            'jumlah' => 100000,
            'branch_id' => $branchId,
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);
        $tagihan = Tagihan::factory()->create([
            'jenis_tagihan_id' => $jenisTagihan->id,
            'nis' => $siswa->nis,
            'branch_id' => $branchId,
        ]);

        for ($i = 0; $i < $jumlahPembayaran; $i++) {
            Pembayaran::factory()->create([
                'kode_tagihan' => $tagihan->kode_tagihan,
                'tanggal' => now()->subDays($i)->format('Y-m-d'),
                'jumlah' => 25000,
                'metode' => 'offline',
                'branch_id' => $branchId,
            ]);
        }

        for ($i = 0; $i < $jumlahPending; $i++) {
            MidtransTransaction::create([
                'order_id' => 'ORDER-PENDING-'.$i,
                'kode_tagihan' => $tagihan->kode_tagihan,
                'nis' => $siswa->nis,
                'amount_paid' => 50000,
                'fee_amount' => 0,
                'gross_amount' => 50000,
                'expired_at' => now()->addDay(),
                'status' => 'pending',
                'branch_id' => $branchId,
            ]);
        }

        $user->update([
            'username' => $siswa->nis,
            'siswa_id' => $siswa->id,
        ]);
        Sanctum::actingAs($user, ['*']);

        return compact('siswa', 'tagihan', 'user');
    }

    /**
     * Regresi: footer paginasi pernah menampilkan "Menampilkan 1 sampai 4 dari
     * 3 hasil" karena pending di-prepend setelah paginator dibuat, sehingga
     * meta.total hanya menghitung pembayaran.
     */
    public function test_pending_midtrans_ikut_dihitung_di_total_paginator(): void
    {
        $this->siswaWithRiwayat(jumlahPembayaran: 3, jumlahPending: 1);

        $response = $this->getJson('/api/pembayaran/siswa?per_page=10&page=1&include_pending=true')
            ->assertOk();

        $json = $response->json();

        $this->assertCount(4, $json['data']);
        $this->assertSame(4, $json['meta']['total']);
        $this->assertSame(1, $json['meta']['from']);
        $this->assertSame(4, $json['meta']['to']);
        $this->assertTrue($json['data'][0]['is_pending']);
    }

    /**
     * Pending menempati slot paling awal daftar gabungan, jadi rentang
     * per halaman harus tetap kontinu saat daftar dipotong.
     */
    public function test_daftar_gabungan_dipaginasi_kontinu_antar_halaman(): void
    {
        $this->siswaWithRiwayat(jumlahPembayaran: 3, jumlahPending: 1);

        $halaman1 = $this->getJson('/api/pembayaran/siswa?per_page=2&page=1&include_pending=true')
            ->assertOk()
            ->json();

        $this->assertCount(2, $halaman1['data']);
        $this->assertSame(4, $halaman1['meta']['total']);
        $this->assertSame(1, $halaman1['meta']['from']);
        $this->assertSame(2, $halaman1['meta']['to']);
        $this->assertTrue($halaman1['data'][0]['is_pending']);
        $this->assertArrayNotHasKey('is_pending', $halaman1['data'][1]);

        $halaman2 = $this->getJson('/api/pembayaran/siswa?per_page=2&page=2&include_pending=true')
            ->assertOk()
            ->json();

        $this->assertCount(2, $halaman2['data']);
        $this->assertSame(4, $halaman2['meta']['total']);
        $this->assertSame(3, $halaman2['meta']['from']);
        $this->assertSame(4, $halaman2['meta']['to']);

        $kodeHalaman1 = array_column($halaman1['data'], 'kode_pembayaran');
        $kodeHalaman2 = array_column($halaman2['data'], 'kode_pembayaran');
        $this->assertCount(4, array_unique(array_merge($kodeHalaman1, $kodeHalaman2)));
    }

    /**
     * Tanpa include_pending, endpoint tetap mengembalikan pembayaran final saja.
     */
    public function test_tanpa_include_pending_total_hanya_menghitung_pembayaran(): void
    {
        $this->siswaWithRiwayat(jumlahPembayaran: 3, jumlahPending: 1);

        $json = $this->getJson('/api/pembayaran/siswa?per_page=10&page=1')
            ->assertOk()
            ->json();

        $this->assertCount(3, $json['data']);
        $this->assertSame(3, $json['meta']['total']);
    }
}
