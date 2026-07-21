<?php

use App\Filament\Pages\ManajemenAkunSiswa;
use Illuminate\Support\Facades\Http;

test('registered akun siswa table supports "all" pagination for bulk print (TA-005)', function () {
    Http::fake([
        '*/akun-siswa*' => Http::response([
            'data' => array_map(
                fn (int $i) => ['id' => $i, 'name' => "Siswa {$i}", 'username' => sprintf('%06d', $i), 'is_active' => true],
                range(1, 37)
            ),
        ], 200),
    ]);

    $page = new ManajemenAkunSiswa;
    $page->tab = ManajemenAkunSiswa::TAB_TERDAFTAR;

    $table = new \Filament\Tables\Table($page);
    $configuredTable = $page->table($table);

    expect($configuredTable->getPaginationPageOptions())->toContain('all');

    $reflection = new ReflectionClass($configuredTable);
    $property = $reflection->getProperty('dataSource');
    $property->setAccessible(true);
    $recordsClosure = $property->getValue($configuredTable);

    $result = $recordsClosure->call($page, search: null, page: 1, recordsPerPage: 'all', filters: []);

    expect($result->total())->toBe(37);
    expect($result->count())->toBe(37);
    expect($result->lastPage())->toBe(1);
});
