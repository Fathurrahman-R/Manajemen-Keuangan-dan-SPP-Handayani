<div class="space-y-6">
    <x-filament::section>
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="flex flex-col md:flex-row gap-3 flex-1 w-full">
                {{-- Search --}}
                <div class="flex-1 max-w-sm">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama atau NIS..."
                        />
                    </x-filament::input.wrapper>
                </div>

                {{-- Jenjang Filter --}}
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filterJenjang">
                        <option value="">Semua Jenjang</option>
                        <option value="TK">TK</option>
                        <option value="KB">KB</option>
                        <option value="MI">MI</option>
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

        <x-filament::section class="overflow-hidden">
            {{-- Card Header --}}
            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $siswa['nama'] }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                NIS: {{ $siswa['nis'] }} |
                                {{ $siswa['jenjang'] }}{{ isset($siswa['kelas']['nama']) ? ' - ' . $siswa['kelas']['nama'] : '' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Dibayar</p>
                        <p class="font-bold text-green-600 dark:text-green-400">Rp. {{ number_format($totalDibayar, 0, '', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-4">
                @if(count($pembayaranList) > 0)
                    <div class="space-y-2">
                        @foreach($pembayaranList as $pembayaran)
                            @php
                                $tanggal = $pembayaran['tanggal'] ?? null;
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition border border-gray-100 dark:border-gray-700">
                                {{-- Date indicator --}}
                                <div class="flex-shrink-0 w-14 h-14 bg-primary-50 dark:bg-primary-900/20 rounded-lg flex flex-col items-center justify-center">
                                    @if($tanggal)
                                        <span class="text-xs font-medium text-primary-600 dark:text-primary-400">{{ \Carbon\Carbon::parse($tanggal)->format('d') }}</span>
                                        <span class="text-[10px] text-primary-500 dark:text-primary-400 uppercase">{{ \Carbon\Carbon::parse($tanggal)->format('M Y') }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>

                                {{-- Payment Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $pembayaran['jenis_tagihan']['nama'] ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Kode: {{ $pembayaran['kode_pembayaran'] }}
                                        · Dibayar oleh: {{ $pembayaran['pembayar'] ?? '-' }}
                                    </p>
                                </div>

                                {{-- Amount + Badge + Actions --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    {{-- Metode Badge --}}
                                    <x-filament::badge :color="($pembayaran['metode'] ?? '') === 'Tunai' ? 'info' : 'gray'">
                                        {{ $pembayaran['metode'] ?? '-' }}
                                    </x-filament::badge>

                                    {{-- Amount --}}
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-semibold text-green-700 dark:text-green-400">Rp. {{ number_format($pembayaran['jumlah'] ?? 0, 0, '', '.') }}</p>
                                        @if(($pembayaran['jenis_tagihan']['jumlah'] ?? 0) > 0)
                                            <p class="text-xs text-gray-400">dari Rp. {{ number_format($pembayaran['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</p>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        {{-- Kwitansi Download --}}
                                        @if(in_array('print-kwitansi', session()->get('data.permissions', [])))
                                            <x-filament::icon-button
                                                icon="heroicon-o-arrow-down-tray"
                                                color="primary"
                                                tooltip="Download Kwitansi"
                                                size="sm"
                                                wire:click="downloadKwitansi('{{ $pembayaran['kode_pembayaran'] }}')"
                                                wire:loading.attr="disabled"
                                            />
                                        @endif

                                        {{-- Delete Button --}}
                                        @if($this->canDelete())
                                            <x-filament::icon-button
                                                icon="heroicon-o-trash"
                                                color="danger"
                                                tooltip="Hapus Pembayaran"
                                                size="sm"
                                                wire:click="deletePembayaran('{{ $pembayaran['kode_pembayaran'] }}')"
                                                wire:confirm="Apakah kamu yakin untuk menghapus pembayaran ini?"
                                                wire:loading.attr="disabled"
                                            />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Belum ada pembayaran.</p>
                @endif
            </div>
        </x-filament::section>
    @empty
        {{-- Empty State --}}
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
                {{-- Per Page Selector --}}
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

                {{-- Page Info --}}
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Halaman {{ $meta['current_page'] ?? 1 }} dari {{ $meta['last_page'] ?? 1 }}
                    ({{ $meta['total'] ?? 0 }} siswa)
                </span>

                {{-- Navigation Buttons --}}
                <div class="flex items-center gap-1 flex-wrap">
                    <x-filament::button
                        outlined
                        size="sm"
                        wire:click="previousPage"
                        :disabled="($meta['current_page'] ?? 1) <= 1"
                    >
                        &laquo; Prev
                    </x-filament::button>

                    @for($i = 1; $i <= ($meta['last_page'] ?? 1); $i++)
                        @if($i <= 3 || $i > ($meta['last_page'] ?? 1) - 3 || abs($i - ($meta['current_page'] ?? 1)) <= 1)
                            <x-filament::button
                                :outlined="$i !== ($meta['current_page'] ?? 1)"
                                :color="$i === ($meta['current_page'] ?? 1) ? 'primary' : 'gray'"
                                size="sm"
                                wire:click="goToPage({{ $i }})"
                            >
                                {{ $i }}
                            </x-filament::button>
                        @elseif($i === 4 || $i === ($meta['last_page'] ?? 1) - 3)
                            <span class="px-2 text-gray-400">...</span>
                        @endif
                    @endfor

                    <x-filament::button
                        outlined
                        size="sm"
                        wire:click="nextPage"
                        :disabled="($meta['current_page'] ?? 1) >= ($meta['last_page'] ?? 1)"
                    >
                        Next &raquo;
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Filament Action Modals --}}
    <x-filament-actions::modals />
</div>
