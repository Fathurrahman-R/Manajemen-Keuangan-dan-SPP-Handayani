<div class="bg-white rounded-lg p-4 flex flex-col gap-y-8 border border-gray-200">
    {{-- Warning banner when no active period --}}
    @if($this->hasNoPeriodeAktif() && $this->hasTahunAjaranOptions())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <span class="text-sm text-yellow-800">Tidak ada periode aktif. Silakan aktifkan tahun ajaran di halaman <a href="/tahun-ajaran-management" class="underline font-medium">Tahun Ajaran</a>.</span>
        </div>
    @endif

    {{-- Period selector --}}
    @if($this->hasTahunAjaranOptions())
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700">Periode:</label>
            <select
                wire:model.live="selectedTahunAjaranId"
                class="border border-gray-300 rounded-lg text-sm py-1.5 px-3 focus:ring-primary-500 focus:border-primary-500"
            >
                @foreach($tahunAjaranOptions as $option)
                    <option value="{{ $option['id'] }}">
                        {{ $option['nama'] }}
                        {{ $option['status'] === 'Aktif' ? '(Aktif)' : '(Historis)' }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="w-full">
        {{ $this->table }}
    </div>
</div>
