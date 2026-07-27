<div class="space-y-4">
    @livewire(\App\Filament\Widgets\PengeluaranStatsWidget::class)

    @if($this->hasTahunAjaranOptions())
        <x-filament::section>
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Periode:</span>
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
        </x-filament::section>
    @endif

    <div>
        {{ $this->table }}
    </div>
</div>
