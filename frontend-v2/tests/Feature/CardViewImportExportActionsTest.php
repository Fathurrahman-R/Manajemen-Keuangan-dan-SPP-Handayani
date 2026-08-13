<?php

use App\Livewire\PembayaranCardView;
use App\Livewire\TagihanCardView;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

test('TagihanCardView exposes working import/template/history actions for tagihan (IE-003)', function () {
    $component = new TagihanCardView;

    expect($component->importTagihanAction())->toBeInstanceOf(Action::class);
    expect($component->templateTagihanAction())->toBeInstanceOf(Action::class);
    expect($component->importHistoryTagihanAction())->toBeInstanceOf(Action::class);
});

test('PembayaranCardView exposes a working export action (IE-005)', function () {
    $component = new PembayaranCardView;

    expect($component->exportPembayaranAction())->toBeInstanceOf(Action::class);
});

/**
 * Regression guard for IE-006: Filament's mountAction() resolves an action by
 * calling method_exists($this, "{$action->getName()}Action"), so the action's
 * name MUST equal the camelCase wrapper method name (minus "Action"). A
 * snake_case name here silently fails to resolve — no modal, no error, the
 * click just does nothing — which is exactly what happened before the fix.
 */
test('import/export/template/history action names match their camelCase wrapper methods', function () {
    $tagihan = new TagihanCardView;
    $pembayaran = new PembayaranCardView;

    expect($tagihan->importTagihanAction()->getName())->toBe('importTagihan');
    expect($tagihan->templateTagihanAction()->getName())->toBe('templateTagihan');
    expect($tagihan->importHistoryTagihanAction()->getName())->toBe('importHistoryTagihan');
    expect($pembayaran->exportPembayaranAction()->getName())->toBe('exportPembayaran');
});

/**
 * Same IE-006 guard as above, for the post-upload preview action added by
 * the all-or-nothing import rework — importPreviewSiswaAction()/
 * importPreviewTagihanAction() must resolve to "importPreviewSiswa"/
 * "importPreviewTagihan", or replaceMountedAction() silently opens nothing.
 */
test('import preview action names match their camelCase wrapper methods', function () {
    $tagihan = new TagihanCardView;

    expect($tagihan->importPreviewTagihanAction()->getName())->toBe('importPreviewTagihan');
});

test('clicking Import on TagihanCardView actually mounts the action and opens a modal (IE-006)', function () {
    Session::put('data.roles', ['superadmin']);

    Livewire::test(TagihanCardView::class, ['jenjang' => 'MI'])
        ->call('mountAction', 'importTagihan')
        ->assertSet('mountedActions.0.name', 'importTagihan');
});
