<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Pengeluaran;
use App\Models\PengeluaranRequest;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengeluaranRequestSeeder extends Seeder
{
    public function run(): void
    {
        $requests = [
            ['uraian' => 'Pembelian kertas A4 5 rim',          'jumlah' => 275000,   'status' => 'draft',     'days_ago' => 2],
            ['uraian' => 'Bayar internet bulan ini',           'jumlah' => 450000,   'status' => 'submitted', 'days_ago' => 5],
            ['uraian' => 'Pembelian tinta printer',            'jumlah' => 180000,   'status' => 'approved',  'days_ago' => 10],
            ['uraian' => 'Biaya cleaning service',             'jumlah' => 600000,   'status' => 'disbursed', 'days_ago' => 20],
            ['uraian' => 'Perbaikan atap bocor',               'jumlah' => 2500000,  'status' => 'rejected',  'days_ago' => 15],
            ['uraian' => 'Pembelian spidol whiteboard 1 lusin', 'jumlah' => 85000,    'status' => 'draft',     'days_ago' => 1],
            ['uraian' => 'Transport rapat koordinasi',         'jumlah' => 300000,   'status' => 'submitted', 'days_ago' => 7],
            ['uraian' => 'Pembelian buku referensi guru',      'jumlah' => 750000,   'status' => 'approved',  'days_ago' => 12],
        ];

        foreach (Branch::all() as $branch) {
            $adminForBranch = User::where('branch_id', $branch->id)->whereHas('roles', function ($q) {
                $q->where('name', 'admin')->orWhere('name', 'superadmin');
            })->first();

            if (! $adminForBranch) {
                $adminForBranch = User::whereHas('roles', function ($q) {
                    $q->where('name', 'superadmin');
                })->first();
            }

            if (! $adminForBranch) {
                continue;
            }

            $aktiveTahunAjaran = TahunAjaran::getAktif($branch->id);

            foreach ($requests as $data) {
                $pengeluaranRequest = PengeluaranRequest::create([
                    'uraian' => $data['uraian'],
                    'jumlah' => $data['jumlah'],
                    'tanggal_kebutuhan' => now()->subDays($data['days_ago'])->format('Y-m-d'),
                    'kategori_pengeluaran' => fake()->randomElement(['ATK', 'Utilitas', 'Perbaikan', 'Kebersihan', null]),
                    'status' => $data['status'],
                    'requester_id' => $adminForBranch->id,
                    'branch_id' => $branch->id,
                ]);

                // Saat status disbursed, buat record Pengeluaran yang terkait
                // agar konsisten dengan apa yang dilakukan WorkflowService::disburse().
                if ($data['status'] === 'disbursed') {
                    Pengeluaran::create([
                        'tanggal' => now()->subDays($data['days_ago'])->format('Y-m-d'),
                        'uraian' => $data['uraian'],
                        'jumlah' => $data['jumlah'],
                        'branch_id' => $branch->id,
                        'tahun_ajaran_id' => $aktiveTahunAjaran?->id,
                        'pengeluaran_request_id' => $pengeluaranRequest->id,
                    ]);
                }
            }
        }
    }
}
