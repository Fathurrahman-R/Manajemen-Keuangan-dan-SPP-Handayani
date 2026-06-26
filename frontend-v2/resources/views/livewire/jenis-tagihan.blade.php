<div class="space-y-4">
    @if($this->hasNoPeriodeAktif() && $this->hasTahunAjaranOptions())
        <x-filament::section>
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-warning-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    Tidak ada periode aktif. Silakan aktifkan tahun ajaran di halaman
                    <a href="/tahun-ajaran-management" class="underline font-medium">Tahun Ajaran</a>.
                </span>
            </div>
        </x-filament::section>
    @endif

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
