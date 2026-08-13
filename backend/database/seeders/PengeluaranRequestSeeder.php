<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Pengeluaran;
use App\Models\PengeluaranRequest;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\Support\TarifDemo;
use Illuminate\Database\Seeder;

/**
 * Permintaan pengeluaran dengan campuran status supaya seluruh alur
 * persetujuan bisa didemokan tanpa perlu membuat data di depan penguji:
 * ada yang masih draft, ada yang menunggu persetujuan, ada yang disetujui tapi
 * belum dicairkan, ada yang ditolak, dan ada yang sudah cair.
 *
 * Permintaan berstatus `disbursed` selalu memunculkan baris `pengeluarans`
 * yang tertaut, meniru apa yang dilakukan WorkflowService::disburse(). Tanpa
 * itu, kas dan daftar permintaan saling bertentangan: permintaan mengaku sudah
 * cair sementara uangnya tidak pernah tercatat keluar.
 */
class PengeluaranRequestSeeder extends Seeder
{
    /**
     * Sebaran status yang wajar: permintaan lama sudah selesai dicairkan, dan
     * hanya sedikit yang masih menggantung di meja.
     *
     * @var list<array{status: string, hari_lalu: int}>
     */
    private const ANTREAN = [
        ['status' => 'draft', 'hari_lalu' => 1],
        ['status' => 'draft', 'hari_lalu' => 3],
        ['status' => 'submitted', 'hari_lalu' => 4],
        ['status' => 'submitted', 'hari_lalu' => 8],
        ['status' => 'approved', 'hari_lalu' => 11],
        ['status' => 'approved', 'hari_lalu' => 16],
        ['status' => 'rejected', 'hari_lalu' => 22],
        ['status' => 'disbursed', 'hari_lalu' => 29],
        ['status' => 'disbursed', 'hari_lalu' => 45],
        ['status' => 'disbursed', 'hari_lalu' => 63],
    ];

    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $pemohon = $this->pemohonUntuk($branch->id);

            if (! $pemohon) {
                continue;
            }

            $tahunAjaranAktif = TahunAjaran::where('branch_id', $branch->id)
                ->where('status', 'Aktif')
                ->first();

            $daftarItem = fake()->randomElements(
                TarifDemo::PENGELUARAN_INSIDENTAL,
                count(self::ANTREAN)
            );

            foreach (self::ANTREAN as $indeks => $antrean) {
                $item = $daftarItem[$indeks];
                $tanggal = now()->subDays($antrean['hari_lalu']);

                $request = PengeluaranRequest::create([
                    'uraian' => $item['uraian'],
                    'jumlah' => $item['jumlah'],
                    'tanggal_kebutuhan' => $tanggal->format('Y-m-d'),
                    'kategori_pengeluaran' => $item['kategori'],
                    'status' => $antrean['status'],
                    'requester_id' => $pemohon->id,
                    'branch_id' => $branch->id,
                ]);

                if ($antrean['status'] === 'disbursed') {
                    Pengeluaran::create([
                        'tanggal' => $tanggal->format('Y-m-d'),
                        'uraian' => $item['uraian'],
                        'jumlah' => $item['jumlah'],
                        'branch_id' => $branch->id,
                        'tahun_ajaran_id' => $tahunAjaranAktif?->id,
                        'pengeluaran_request_id' => $request->id,
                    ]);
                }
            }
        }
    }

    private function pemohonUntuk(int $branchId): ?User
    {
        $pemohon = User::where('branch_id', $branchId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))
            ->first();

        return $pemohon ?? User::whereHas('roles', fn ($q) => $q->where('name', 'superadmin'))->first();
    }
}
