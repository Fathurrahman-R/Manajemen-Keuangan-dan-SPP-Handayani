<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            @if(count($childOptions) > 1)
                <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Pilih Anak:</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedSiswaId">
                            @foreach($childOptions as $child)
                                <option value="{{ $child['id'] }}">{{ $child['nama'] }} ({{ $child['nis'] }})</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            @if($this->hasTahunAjaranOptions())
                <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Periode:</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedTahunAjaranId">
                            <option value="">Semua Periode</option>
                            @foreach($tahunAjaranOptions as $option)
                                <option value="{{ $option['id'] }}">
                                    {{ $option['nama'] }}
                                    {{ $option['status'] === 'Aktif' ? '(Aktif)' : '(Historis)' }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif
        </div>
    </x-filament::section>

    <div wire:loading.class="opacity-50 pointer-events-none transition-opacity" wire:target="selectedTahunAjaranId, selectedSiswaId" class="space-y-6">
        @livewire(
            \App\Filament\Widgets\PortalSiswaStatsWidget::class,
            ['selectedSiswaId' => $this->selectedSiswaId, 'selectedTahunAjaranId' => $this->selectedTahunAjaranId],
            key('portal-stats-' . ($this->selectedSiswaId ?? 'self') . '-' . ($this->selectedTahunAjaranId ?? 'all'))
        )

        <x-filament::section>
            <x-slot name="heading">Daftar Tagihan</x-slot>
            @livewire(
                \App\Livewire\PortalSiswaTagihanTable::class,
                ['selectedSiswaId' => $this->selectedSiswaId, 'selectedTahunAjaranId' => $this->selectedTahunAjaranId],
                key('portal-tagihan-' . ($this->selectedSiswaId ?? 'self') . '-' . ($this->selectedTahunAjaranId ?? 'all'))
            )
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Pembayaran Terbaru</x-slot>
            @livewire(
                \App\Livewire\PortalSiswaPembayaranTable::class,
                ['selectedSiswaId' => $this->selectedSiswaId, 'selectedTahunAjaranId' => $this->selectedTahunAjaranId],
                key('portal-pembayaran-' . ($this->selectedSiswaId ?? 'self') . '-' . ($this->selectedTahunAjaranId ?? 'all'))
            )
        </x-filament::section>
    </div>
</x-filament-panels::page>
