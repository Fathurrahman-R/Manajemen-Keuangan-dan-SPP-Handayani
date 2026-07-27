<?php

use App\Livewire\ImportHistoryTable;
use Illuminate\Support\Facades\Http;

test('ImportHistoryTable records() only returns rows matching its importType (IE-002)', function () {
    Http::fake([
        '*/import-export/import/history*' => Http::response([
            'data' => [
                ['import_type' => 'siswa', 'file_name' => 'siswa.xlsx', 'status' => 'completed'],
                ['import_type' => 'tagihan', 'file_name' => 'tagihan.xlsx', 'status' => 'completed'],
                ['import_type' => 'siswa', 'file_name' => 'siswa2.xlsx', 'status' => 'failed'],
            ],
        ], 200),
    ]);

    session(['api_token' => 'fake-token', 'branch_id' => 1]);

    $component = new ImportHistoryTable;
    $component->mount('siswa');

    $table = new \Filament\Tables\Table($component);
    $configuredTable = $component->table($table);

    $reflection = new ReflectionClass($configuredTable);
    $property = $reflection->getProperty('dataSource');
    $property->setAccessible(true);
    $recordsClosure = $property->getValue($configuredTable);

    $records = $recordsClosure->call($component);

    expect($records)->toHaveCount(2);
    expect(collect($records)->pluck('file_name')->all())->toBe(['siswa.xlsx', 'siswa2.xlsx']);
});
