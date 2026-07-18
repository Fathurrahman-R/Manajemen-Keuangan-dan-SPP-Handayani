<x-filament-panels::page>
    {{-- ============================================================
         Section 1: SEMUA PERIODE
         Stat all-time tampil di atas dropdown periode supaya pengguna
         tahu angka ini tidak terikat ke periode yang sedang dipilih.
         ============================================================ --}}
    <x-filament::section>
        <x-slot name="heading">Semua Periode</x-slot>
        <x-slot name="description">Akumulasi data lintas semua tahun ajaran</x-slot>

        @livewire(\App\Filament\Widgets\DashboardAllTimeStatsWidget::class)
    </x-filament::section>

    {{-- ============================================================
         Section 2: PERIODE INI
         Dropdown periode + semua widget yang bergantung pada periode.
         ============================================================ --}}
    <x-filament::section>
        <x-slot name="heading">Periode Ini</x-slot>
        <x-slot name="description">Statistik dan grafik untuk periode terpilih</x-slot>

        <x-slot name="afterHeader">
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Periode:</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="selectedTahunAjaranId">
                        <!-- <option value="">Semua Periode</option> -->
                        @foreach($tahunAjaranOptions as $option)
                            <option value="{{ $option['id'] }}">
                                {{ $option['nama'] }}{{ $option['status'] === 'Aktif' ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <div wire:loading wire:target="selectedTahunAjaranId" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::loading-indicator class="h-4 w-4" />
                    Memuat...
                </div>
            </div>
        </x-slot>

        <div wire:loading.class="opacity-50 pointer-events-none transition-opacity" wire:target="selectedTahunAjaranId" class="space-y-6">
            {{-- KPI inti periode --}}
            @livewire(\App\Filament\Widgets\DashboardStatsWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('stats-periode-'.$this->selectedTahunAjaranId))

            {{-- Kas periode (pemasukan/pengeluaran/saldo) --}}
            @livewire(\App\Filament\Widgets\DashboardKasStatsWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('stats-kas-periode-'.$this->selectedTahunAjaranId))

            {{-- Chart full-width — pembayaran bulanan & kas bulanan --}}
            <div class="grid grid-cols-1 gap-6">
                @livewire(\App\Filament\Widgets\PembayaranBulananChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-pembayaran-'.$this->selectedTahunAjaranId))
                @livewire(\App\Filament\Widgets\KasBulananChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-kas-'.$this->selectedTahunAjaranId))
            </div>

            {{-- Chart 2 col berdampingan — tunggakan jenjang & status tagihan --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @livewire(\App\Filament\Widgets\TunggakanJenjangChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-tunggakan-'.$this->selectedTahunAjaranId))
                @livewire(\App\Filament\Widgets\StatusTagihanChart::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('chart-status-'.$this->selectedTahunAjaranId))
            </div>

            {{-- Tabel ringkas --}}
            <div class="space-y-6">
                @livewire(\App\Filament\Widgets\TopTunggakanWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-tunggakan-'.$this->selectedTahunAjaranId))
                @livewire(\App\Filament\Widgets\TagihanJatuhTempoWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-jatuh-tempo-'.$this->selectedTahunAjaranId))
                @livewire(\App\Filament\Widgets\PembayaranTerbaruWidget::class, ['selectedTahunAjaranId' => $this->selectedTahunAjaranId], key('table-pembayaran-'.$this->selectedTahunAjaranId))
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
