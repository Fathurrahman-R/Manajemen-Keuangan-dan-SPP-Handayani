<x-filament-panels::page>
    {{-- Period Selector (Filament Select) --}}
    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Periode:</label>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedTahunAjaranId">
                @foreach($tahunAjaranOptions as $option)
                    <option value="{{ $option['id'] }}">
                        {{ $option['nama'] }}{{ $option['status'] === 'Aktif' ? ' (Aktif)' : '' }}
                    </option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    {{-- Widgets are rendered automatically via getHeaderWidgets() and getFooterWidgets() --}}
    @livewire(\App\Filament\Widgets\DashboardStatsWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId])

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        @livewire(\App\Filament\Widgets\PembayaranBulananChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-pembayaran-'.$this->selectedTahunAjaranId))
        @livewire(\App\Filament\Widgets\TunggakanJenjangChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-tunggakan-'.$this->selectedTahunAjaranId))
        @livewire(\App\Filament\Widgets\KasBulananChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-kas-'.$this->selectedTahunAjaranId))
        @livewire(\App\Filament\Widgets\StatusTagihanChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-status-'.$this->selectedTahunAjaranId))
    </div>

    <div class="mt-6 space-y-6">
        @livewire(\App\Filament\Widgets\TopTunggakanWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-tunggakan-'.$this->selectedTahunAjaranId))
        @livewire(\App\Filament\Widgets\TagihanJatuhTempoWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-jatuh-tempo-'.$this->selectedTahunAjaranId))
        @livewire(\App\Filament\Widgets\PembayaranTerbaruWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-pembayaran-'.$this->selectedTahunAjaranId))
    </div>
</x-filament-panels::page>
