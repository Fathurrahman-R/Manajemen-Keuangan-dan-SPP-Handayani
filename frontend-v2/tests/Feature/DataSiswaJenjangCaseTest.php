<?php

use App\Livewire\DataSiswa;
use Livewire\Livewire;

test('activeTab dinormalisasi uppercase walau jenjang datang lowercase dari breadcrumb', function () {
    Livewire::test(DataSiswa::class, ['jenjang' => 'mi'])
        ->assertSet('activeTab', 'MI');
});
