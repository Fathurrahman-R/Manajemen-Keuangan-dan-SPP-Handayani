<x-filament-panels::page>
    @if(count($childOptions) > 1)
        <x-filament::section>
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
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">Daftar Tagihan</x-slot>
        @livewire(
            \App\Livewire\PortalSiswaTagihanTable::class,
            ['selectedSiswaId' => $this->selectedSiswaId],
            key('portal-tagihan-' . ($this->selectedSiswaId ?? 'self'))
        )
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Pembayaran Terbaru</x-slot>
        @livewire(
            \App\Livewire\PortalSiswaPembayaranTable::class,
            ['selectedSiswaId' => $this->selectedSiswaId],
            key('portal-pembayaran-' . ($this->selectedSiswaId ?? 'self'))
        )
    </x-filament::section>
</x-filament-panels::page>
