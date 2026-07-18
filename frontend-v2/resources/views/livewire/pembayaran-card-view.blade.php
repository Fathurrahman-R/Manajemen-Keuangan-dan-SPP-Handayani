<div class="space-y-6 relative">
    {{-- Loading overlay --}}
    <div wire:loading.flex wire:target="loadData, search, filterJenjang, filterKelas, filterMetode, sort, perPage, selectedTahunAjaranId, goToPage, previousPage, nextPage" class="absolute inset-0 z-10 items-center justify-center bg-white/60 dark:bg-gray-900/60 rounded-xl">
        <div class="flex flex-col items-center gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Memuat...</p>
        </div>
    </div>

    {{-- Filters --}}
    <x-filament::section>
        {{-- Header bar --}}
        <div class="flex items-center gap-2 mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">
            <x-heroicon-o-funnel class="h-4 w-4 text-gray-400" />
            <span>Filter Pembayaran</span>
        </div>

        {{-- Search full width --}}
        <div class="mb-3">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau NIS siswa..."
                />
            </x-filament::input.wrapper>
        </div>

        {{-- Filter dropdown rows --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
            @if($this->hasTahunAjaranOptions())
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Periode Ajaran</label>
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

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Jenjang</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filterJenjang">
                        <option value="">Semua Jenjang</option>
                        <option value="TK">TK</option>
                        <option value="KB">KB</option>
                        <option value="MI">MI</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kelas</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filterKelas">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Metode Pembayaran</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filterMetode">
                        <option value="">Semua Metode</option>
                        <option value="offline">Offline (Tunai/Transfer)</option>
                        <option value="online_midtrans">Online (Midtrans)</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Urutkan</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="sort">
                        <option value="latest">Pembayaran Terbaru</option>
                        <option value="oldest">Pembayaran Terlama</option>
                        <option value="nama">Nama (A–Z)</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    {{-- Siswa Cards --}}
    @forelse($siswaData as $siswa)
        @php
            $pembayaranList = $siswa['pembayaran'] ?? [];
            $totalDibayar = collect($pembayaranList)->sum(fn($p) => $p['jumlah'] ?? 0);
        @endphp

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Header: Profil --}}
            <div class="px-4 pt-4 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-primary-700 dark:text-primary-300">{{ strtoupper(substr($siswa['nama'], 0, 2)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $siswa['nama'] }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            NIS: {{ $siswa['nis'] }} · {{ $siswa['jenjang'] }}{{ isset($siswa['kelas']['nama']) ? ' – ' . $siswa['kelas']['nama'] : '' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Header: Total Dibayar --}}
            @if($totalDibayar > 0)
                <div class="mx-4 mb-3 px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center justify-between">
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium">Total dibayar</span>
                    <span class="text-base font-bold text-green-700 dark:text-green-300">Rp. {{ number_format($totalDibayar, 0, '', '.') }}</span>
                </div>
            @endif

            {{-- Body: Pembayaran List --}}
            <div class="px-4 pb-4">
                @if(count($pembayaranList) > 0)
                    <div class="space-y-3">
                        @foreach($pembayaranList as $pembayaran)
                            @php $tanggal = $pembayaran['tanggal'] ?? null; @endphp
                            <div class="border-l-4 border-green-300 dark:border-green-700 pl-3 py-2">
                                {{-- Row 1: Nama + Nominal --}}
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $pembayaran['jenis_tagihan']['nama'] ?? '-' }}</span>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-bold text-green-700 dark:text-green-400">Rp. {{ number_format($pembayaran['jumlah'] ?? 0, 0, '', '.') }}</p>
                                        @if(($pembayaran['jenis_tagihan']['jumlah'] ?? 0) > 0)
                                            <p class="text-xs text-gray-400">dari Rp. {{ number_format($pembayaran['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Row 2: Tanggal + Kode + Badge metode --}}
                                <div class="flex items-center gap-2 flex-wrap mt-1">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $tanggal ? \Carbon\Carbon::parse($tanggal)->format('d M Y') : '-' }}
                                        · {{ $pembayaran['kode_pembayaran'] }}
                                        · {{ $pembayaran['pembayar'] ?? '-' }}
                                    </span>
                                    <x-filament::badge :color="($pembayaran['metode'] ?? '') === 'online_midtrans' ? 'success' : 'gray'" size="sm">
                                        {{ ($pembayaran['metode'] ?? '') === 'online_midtrans' ? 'Online' : 'Offline' }}
                                    </x-filament::badge>
                                </div>

                                {{-- Row 3: Action buttons --}}
                                <div class="flex items-center gap-2 mt-2">
                                    @if(\App\Helpers\PermissionHelper::hasResource('pembayaran.kwitansi'))
                                        <button
                                            type="button"
                                            wire:click="downloadKwitansi('{{ $pembayaran['kode_pembayaran'] }}')"
                                            wire:loading.attr="disabled"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg border border-primary-300 dark:border-primary-700 text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/40 transition"
                                        >
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                            Kwitansi
                                        </button>
                                    @endif
                                    @if($this->canDelete())
                                        <button
                                            type="button"
                                            wire:click="deletePembayaran('{{ $pembayaran['kode_pembayaran'] }}')"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 bg-white dark:bg-gray-900 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                        >
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Belum ada pembayaran.</p>
                @endif
            </div>
        </div>
    @empty
        <x-filament::section>
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak Ada Pembayaran</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if(filled($search) || filled($filterJenjang))
                        Tidak ada siswa yang cocok dengan filter yang diterapkan.
                    @else
                        Belum ada pembayaran yang tersedia.
                    @endif
                </p>
            </div>
        </x-filament::section>
    @endforelse

    {{-- Pagination --}}
    @if(($meta['last_page'] ?? 1) > 1)
        <x-filament::section>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Per halaman:</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="perPage">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Halaman {{ $meta['current_page'] ?? 1 }} dari {{ $meta['last_page'] ?? 1 }}
                    ({{ $meta['total'] ?? 0 }} siswa)
                </span>
                <div class="flex items-center gap-1 flex-wrap">
                    <x-filament::button outlined size="sm" wire:click="previousPage" :disabled="($meta['current_page'] ?? 1) <= 1">&laquo; Prev</x-filament::button>
                    @for($i = 1; $i <= ($meta['last_page'] ?? 1); $i++)
                        @if($i <= 3 || $i > ($meta['last_page'] ?? 1) - 3 || abs($i - ($meta['current_page'] ?? 1)) <= 1)
                            <x-filament::button :outlined="$i !== ($meta['current_page'] ?? 1)" :color="$i === ($meta['current_page'] ?? 1) ? 'primary' : 'gray'" size="sm" wire:click="goToPage({{ $i }})">{{ $i }}</x-filament::button>
                        @elseif($i === 4 || $i === ($meta['last_page'] ?? 1) - 3)
                            <span class="px-2 text-gray-400">...</span>
                        @endif
                    @endfor
                    <x-filament::button outlined size="sm" wire:click="nextPage" :disabled="($meta['current_page'] ?? 1) >= ($meta['last_page'] ?? 1)">Next &raquo;</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

    <x-filament-actions::modals />

    @script
    <script>
        $wire.on('scroll-to-top', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
    @endscript
</div>

