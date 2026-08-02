<?php

use App\Livewire\KenaikanKelas;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

/**
 * Regression: KenaikanKelasService::getNextKelas() now rejects an ambiguous
 * next-level target instead of silently picking one — level is no longer
 * unique per jenjang+branch (see include_nama_in_kelas_level_unique
 * migration). The Livewire component must surface a manual picker instead
 * of letting the admin submit into that wall with no way out.
 */
test('shows a manual target-kelas picker when next level is ambiguous and blocks submit until chosen', function () {
    Session::put('data.roles', ['superadmin']);
    Session::put('data.permissions', []);

    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, '/tahun-ajaran')) {
            return Http::response(['data' => [
                ['id' => 1, 'nama' => '2025/2026', 'status' => 'Aktif'],
                ['id' => 2, 'nama' => '2026/2027', 'status' => 'Non-Aktif'],
            ]], 200);
        }

        if (str_contains($url, '/kenaikan-kelas/class-hierarchy')) {
            return Http::response(['data' => [
                ['id' => 10, 'nama' => 'MATAHARI', 'level' => 1, 'jenjang' => 'TK'],
                ['id' => 11, 'nama' => 'BINTANG', 'level' => 2, 'jenjang' => 'TK'],
                ['id' => 12, 'nama' => 'BULAN', 'level' => 2, 'jenjang' => 'TK'],
            ]], 200);
        }

        if (str_contains($url, '/kenaikan-kelas/eligible-students')) {
            return Http::response(['data' => [
                ['id' => 100, 'nis' => '0001', 'nama' => 'Siswa A', 'status' => 'Aktif'],
            ]], 200);
        }

        return Http::response(['data' => []], 200);
    });

    $component = Livewire::test(KenaikanKelas::class)
        ->set('activeJenjangTab', 'TK')
        ->set('selectedKelasId', 10)
        ->set('selectedTargetPeriodId', 2);

    $component->assertSet('nextLevelCandidates', [
        ['id' => 11, 'nama' => 'BINTANG', 'level' => 2, 'jenjang' => 'TK'],
        ['id' => 12, 'nama' => 'BULAN', 'level' => 2, 'jenjang' => 'TK'],
    ]);

    // Belum pilih kelas tujuan manual -> processAll harus nolak tanpa
    // pernah memanggil bulk-promotion sama sekali.
    $component->call('processAll');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/kenaikan-kelas/bulk-promotion'));

    // Setelah admin pilih BULAN secara manual -> request harus menyertakan
    // target_kelas_id yang dipilih.
    $component->set('ambiguousTargetKelasId', 12)
        ->call('processAll');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/kenaikan-kelas/bulk-promotion')
        && $request['target_kelas_id'] === 12);
});
