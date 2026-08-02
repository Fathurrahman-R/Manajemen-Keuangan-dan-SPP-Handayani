<?php

use App\Livewire\DataKelas;
use Illuminate\Support\Facades\Http;

/**
 * Regression guard: the delete/bulk-delete actions must call
 * DELETE /kelas/{jenjang}/{id} — the backend route requires the jenjang
 * segment (routes/api.php), but the action closures used to build the URL
 * without it and silently failed on every click.
 */
function getDataKelasRecordAction(DataKelas $component, string $name): Closure
{
    $table = new \Filament\Tables\Table($component);
    $configured = $component->table($table);

    foreach ($configured->getRecordActions() as $action) {
        if ($action->getName() === $name) {
            return $action->getActionFunction();
        }
    }

    throw new RuntimeException("Action [{$name}] not found on DataKelas table.");
}

test('delete kelas action calls the jenjang-scoped endpoint', function () {
    Http::fake([
        '*' => Http::response(['data' => true], 200),
    ]);

    $component = new DataKelas;
    $component->activeTab = 'TK';

    $action = getDataKelasRecordAction($component, 'delete');
    $action->call($component, [], ['id' => 42, 'nama' => 'TK A']);

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && str_contains($request->url(), '/kelas/TK/42'));
});

test('bulk delete kelas calls the jenjang-scoped endpoint for every selected record', function () {
    Http::fake([
        '*' => Http::response(['data' => true], 200),
    ]);

    $component = new DataKelas;
    $component->activeTab = 'KB';

    $table = new \Filament\Tables\Table($component);
    $configured = $component->table($table);

    $bulkAction = collect($configured->getBulkActions())
        ->first(fn ($action) => $action->getName() === 'bulkDelete');

    expect($bulkAction)->not->toBeNull();

    $records = collect([
        ['id' => 1, 'nama' => 'KB A'],
        ['id' => 2, 'nama' => 'KB B'],
    ]);

    $bulkAction->getActionFunction()->call($component, $records);

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && str_contains($request->url(), '/kelas/KB/1'));
    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && str_contains($request->url(), '/kelas/KB/2'));
});
