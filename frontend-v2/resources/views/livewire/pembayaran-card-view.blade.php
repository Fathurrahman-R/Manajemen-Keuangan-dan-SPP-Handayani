<div class="space-y-6">
    <x-filament::section>
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="flex flex-col md:flex-row gap-3 flex-1 w-full">
                {{-- Search --}}
                <div class="relative flex-1 max-w-sm">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama atau NIS..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400"
                    />
                    <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                {{-- Jenjang Filter --}}
                <select
                    wire:model.live="filterJenjang"
                    class="border border-gray-300 dark:border-gray-600 rounded-lg text-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:text-gray-100"
                >
                    <option value="">Semua Jenjang</option>
                    <option value="TK">TK</option>
                    <option value="KB">KB</option>
                    <option value="MI">MI</option>
                </select>
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
                <div class="flex items-center justify-between">
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
                                $metodeColor = ($pembayaran['metode'] ?? '') === 'Tunai' ? 'blue' : 'purple';
                            @endphp
                            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition border border-gray-100 dark:border-gray-700">
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
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $pembayaran['jenis_tagihan']['nama'] ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Kode: {{ $pembayaran['kode_pembayaran'] }}
                                        · Dibayar oleh: {{ $pembayaran['pembayar'] ?? '-' }}
                                    </p>
                                </div>

                                {{-- Amount --}}
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-semibold text-green-700 dark:text-green-400">Rp. {{ number_format($pembayaran['jumlah'] ?? 0, 0, '', '.') }}</p>
                                    @if(($pembayaran['jenis_tagihan']['jumlah'] ?? 0) > 0)
                                        <p class="text-xs text-gray-400">dari Rp. {{ number_format($pembayaran['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</p>
                                    @endif
                                </div>

                                {{-- Metode Badge --}}
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    {{ $metodeColor === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' }}">
                                    {{ $pembayaran['metode'] ?? '-' }}
                                </span>

                                {{-- Actions --}}
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    {{-- Kwitansi Download --}}
                                    @if(in_array('print-kwitansi', session()->get('data.permissions', [])))
                                        <button
                                            wire:click="downloadKwitansi('{{ $pembayaran['kode_pembayaran'] }}')"
                                            wire:loading.attr="disabled"
                                            class="text-gray-400 hover:text-primary-600 p-1 rounded transition"
                                            title="Download Kwitansi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Delete Button --}}
                                    @if($this->canDelete())
                                        <button
                                            wire:click="deletePembayaran('{{ $pembayaran['kode_pembayaran'] }}')"
                                            wire:confirm="Apakah kamu yakin untuk menghapus pembayaran ini?"
                                            wire:loading.attr="disabled"
                                            class="text-red-400 hover:text-red-600 p-1 rounded transition"
                                            title="Hapus Pembayaran"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
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
                <div class="flex items-center gap-1">
                    <button
                        wire:click="previousPage"
                        @disabled(($meta['current_page'] ?? 1) <= 1)
                        class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition dark:text-gray-300"
                    >
                        &laquo; Prev
                    </button>

                    @for($i = 1; $i <= ($meta['last_page'] ?? 1); $i++)
                        @if($i <= 3 || $i > ($meta['last_page'] ?? 1) - 3 || abs($i - ($meta['current_page'] ?? 1)) <= 1)
                            <button
                                wire:click="goToPage({{ $i }})"
                                class="px-3 py-1 text-sm border rounded-lg transition
                                    {{ $i === ($meta['current_page'] ?? 1) ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-300' }}"
                            >
                                {{ $i }}
                            </button>
                        @elseif($i === 4 || $i === ($meta['last_page'] ?? 1) - 3)
                            <span class="px-2 text-gray-400">...</span>
                        @endif
                    @endfor

                    <button
                        wire:click="nextPage"
                        @disabled(($meta['current_page'] ?? 1) >= ($meta['last_page'] ?? 1))
                        class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition dark:text-gray-300"
                    >
                        Next &raquo;
                    </button>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Filament Action Modals --}}
    <x-filament-actions::modals />
</div>
