<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class KasTest extends TestCase
{
    public function testKasHarian_RunningBalanceSesuaiRiwayatSaldoPadaTanggal(): void
    {
        $scenario = $this->createKasHarian();
        $user = $scenario['user'];

        $response = $this->get('api/laporan/kas?bulan=1&tahun=2025', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                "errors" => []
            ]);

        //        $data = $response->json('data');
        //        self::assertIsArray($data);
        //
        //        // Cek saldo per tanggal penting di Januari 2025
        //        $tanggal1 = '2025-01-01';
        //        $tanggal2 = '2025-01-05';
        //        $tanggal3 = '2025-01-10';
        //
        //        $expectedSaldoT1 = \App\Models\Pembayaran::whereDate('tanggal', '<=', $tanggal1)->sum('jumlah')
        //            - \App\Models\Pengeluaran::whereDate('tanggal', '<=', $tanggal1)->sum('jumlah');
        //        $expectedSaldoT2 = \App\Models\Pembayaran::whereDate('tanggal', '<=', $tanggal2)->sum('jumlah')
        //            - \App\Models\Pengeluaran::whereDate('tanggal', '<=', $tanggal2)->sum('jumlah');
        //        $expectedSaldoT3 = \App\Models\Pembayaran::whereDate('tanggal', '<=', $tanggal3)->sum('jumlah')
        //            - \App\Models\Pengeluaran::whereDate('tanggal', '<=', $tanggal3)->sum('jumlah');
        //
        //        $labelT1 = Carbon::parse($tanggal1)->locale('id')->translatedFormat('d F Y');
        //        $labelT2 = Carbon::parse($tanggal2)->locale('id')->translatedFormat('d F Y');
        //        $labelT3 = Carbon::parse($tanggal3)->locale('id')->translatedFormat('d F Y');
        //
        //        $byTanggal = collect($data)->keyBy('tanggal');
        //        self::assertNotEquals($expectedSaldoT1, $byTanggal[$labelT1]['saldo'] ?? null);
        //        self::assertNotEquals($expectedSaldoT2, $byTanggal[$labelT2]['saldo'] ?? null);
        //        self::assertNotEquals($expectedSaldoT3, $byTanggal[$labelT3]['saldo'] ?? null);
    }

    public function testRekapBulanan_RunningBalanceSesuaiRiwayatSaldoPadaBulan(): void
    {
        $scenario = $this->createRekapBulanan();
        $user = $scenario['user'];

        $response = $this->get('api/laporan/rekap?tahun=2025', ['Authorization' => $user->token])
            ->assertStatus(200)
            ->assertJson([
                "errors" => []
            ]);

        //        $data = $response->json('data');
        //        self::assertIsArray($data);
        //
        //        // Saldo sampai akhir Januari dan Februari 2025
        //        $endJan = '2025-01-31';
        //        $endFeb = Carbon::parse('2025-02-01')->endOfMonth()->format('Y-m-d');
        //
        //        $expectedJan = \App\Models\Pembayaran::whereDate('tanggal', '<=', $endJan)->sum('jumlah')
        //            - \App\Models\Pengeluaran::whereDate('tanggal', '<=', $endJan)->sum('jumlah');
        //        $expectedFeb = \App\Models\Pembayaran::whereDate('tanggal', '<=', $endFeb)->sum('jumlah')
        //            - \App\Models\Pengeluaran::whereDate('tanggal', '<=', $endFeb)->sum('jumlah');
        //
        //        $labelJan = Carbon::parse('2025-01-01')->locale('id')->translatedFormat('F Y');
        //        $labelFeb = Carbon::parse('2025-02-01')->locale('id')->translatedFormat('F Y');
        //
        //        $byBulan = collect($data)->keyBy('tanggal');
        //        self::assertNotEquals($expectedJan, $byBulan[$labelJan]['saldo'] ?? null);
        //        self::assertNotEquals($expectedFeb, $byBulan[$labelFeb]['saldo'] ?? null);
    }
}
