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

        {{-- Inline spinner saat ubah periode --}}
        <div wire:loading wire:target="selectedTahunAjaranId" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <x-filament::loading-indicator class="h-4 w-4" />
            Memuat...
        </div>
    </div>

    <div wire:loading.class="opacity-50 pointer-events-none transition-opacity" wire:target="selectedTahunAjaranId" class="space-y-6">
        {{-- Row 1: Stat periode (KPI inti) --}}
        @livewire(\App\Filament\Widgets\DashboardStatsWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('stats-periode-'.$this->selectedTahunAjaranId))

        {{-- Row 2: Stat kas periode (pemasukan vs pengeluaran vs saldo) --}}
        @livewire(\App\Filament\Widgets\DashboardKasStatsWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('stats-kas-periode-'.$this->selectedTahunAjaranId))

        {{-- Row 3: Stat all-time (tidak terikat periode) --}}
        @livewire(\App\Filament\Widgets\DashboardAllTimeStatsWidget::class)

        {{-- Row 4: Chart 2 col penuh — pembayaran bulanan & kas bulanan (lebar) --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Widgets\PembayaranBulananChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-pembayaran-'.$this->selectedTahunAjaranId))
            @livewire(\App\Filament\Widgets\KasBulananChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-kas-'.$this->selectedTahunAjaranId))
        </div>

        {{-- Row 5: Chart 1 col berdampingan — tunggakan jenjang & status tagihan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @livewire(\App\Filament\Widgets\TunggakanJenjangChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-tunggakan-'.$this->selectedTahunAjaranId))
            @livewire(\App\Filament\Widgets\StatusTagihanChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-status-'.$this->selectedTahunAjaranId))
        </div>

        {{-- Row 6: Tabel ringkas --}}
        <div class="space-y-6">
            @livewire(\App\Filament\Widgets\TopTunggakanWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-tunggakan-'.$this->selectedTahunAjaranId))
            @livewire(\App\Filament\Widgets\TagihanJatuhTempoWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-jatuh-tempo-'.$this->selectedTahunAjaranId))
            @livewire(\App\Filament\Widgets\PembayaranTerbaruWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-pembayaran-'.$this->selectedTahunAjaranId))
        </div>
    </div>
</x-filament-panels::page>
