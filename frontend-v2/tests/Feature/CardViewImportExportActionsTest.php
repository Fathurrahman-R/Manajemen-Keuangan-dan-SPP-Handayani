<?php

use App\Livewire\PembayaranCardView;
use App\Livewire\TagihanCardView;
use Filament\Actions\Action;

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
