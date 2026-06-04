<div class="space-y-6">
    {{-- Warning banner when no active period --}}
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

    <x-filament::section>
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="flex flex-col md:flex-row gap-3 flex-1 w-full">
                {{-- Period Selector (Filament Select) --}}
                @if($this->hasTahunAjaranOptions())
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedTahunAjaranId">
                            @foreach($tahunAjaranOptions as $option)
                                <option value="{{ $option['id'] }}">
                                    {{ $option['nama'] }}
                                    {{ $option['status'] === 'Aktif' ? '(Aktif)' : '(Historis)' }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif

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

                {{-- Status Filter --}}
                <select
                    wire:model.live="filterStatus"
                    class="border border-gray-300 dark:border-gray-600 rounded-lg text-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:text-gray-100"
                >
                    <option value="">Semua Status</option>
                    <option value="Belum Dibayar">Belum Dibayar</option>
                    <option value="Belum Lunas">Belum Lunas</option>
                    <option value="Lunas">Lunas</option>
                </select>
            </div>

            {{-- Add Tagihan Button --}}
            @if($this->canCreate())
                <div>
                    {{ $this->addTagihanAction }}
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Student View: Summary (for non-admin users) --}}
    @if(!$this->isAdmin() && count($siswaData) > 0)
        @php
            $allTagihan = collect($siswaData)->flatMap(fn($s) => $s['tagihan'] ?? []);
            $totalJumlah = $allTagihan->sum(fn($t) => $t['jenis_tagihan']['jumlah'] ?? 0);
            $totalTmp = $allTagihan->sum(fn($t) => $t['tmp'] ?? 0);
            $totalSisa = $totalJumlah - $totalTmp;
        @endphp
        <x-filament::section>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ringkasan Tagihan</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3">
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Tagihan</p>
                    <p class="text-lg font-bold text-blue-800 dark:text-blue-300">Rp. {{ number_format($totalJumlah, 0, '', '.') }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-3">
                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">Sudah Dibayar</p>
                    <p class="text-lg font-bold text-green-800 dark:text-green-300">Rp. {{ number_format($totalTmp, 0, '', '.') }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-3">
                    <p class="text-xs text-red-600 dark:text-red-400 font-medium">Sisa Tagihan</p>
                    <p class="text-lg font-bold text-red-800 dark:text-red-300">Rp. {{ number_format($totalSisa, 0, '', '.') }}</p>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Siswa Cards --}}
    @forelse($siswaData as $siswa)
        @php
            $tagihanList = $siswa['tagihan'] ?? [];
            $unpaid = collect($tagihanList)->filter(fn($t) => $t['status'] !== 'Lunas')->values()->toArray();
            $paid = collect($tagihanList)->filter(fn($t) => $t['status'] === 'Lunas')->values()->toArray();

            // Sort by jatuh_tempo for student view
            if (!$this->isAdmin()) {
                usort($unpaid, fn($a, $b) => strcmp($a['jenis_tagihan']['jatuh_tempo'] ?? '', $b['jenis_tagihan']['jatuh_tempo'] ?? ''));
                usort($paid, fn($a, $b) => strcmp($a['jenis_tagihan']['jatuh_tempo'] ?? '', $b['jenis_tagihan']['jatuh_tempo'] ?? ''));
            }

            $totalSisaCard = collect($unpaid)->sum(fn($t) => ($t['jenis_tagihan']['jumlah'] ?? 0) - ($t['tmp'] ?? 0));
        @endphp

        <x-filament::section x-data="{
                selectedTagihan: [],
                unpaidItems: @js($unpaid),
                get allSelected() {
                    return this.unpaidItems.length > 0 && this.selectedTagihan.length === this.unpaidItems.length;
                },
                get rekapTotal() {
                    return this.unpaidItems
                        .filter(t => this.selectedTagihan.includes(t.kode_tagihan))
                        .reduce((sum, t) => sum + ((t.jenis_tagihan?.jumlah || 0) - (t.tmp || 0)), 0);
                },
                get selectedItems() {
                    return this.unpaidItems.filter(t => this.selectedTagihan.includes(t.kode_tagihan));
                },
                toggleAll() {
                    if (this.allSelected) {
                        this.selectedTagihan = [];
                    } else {
                        this.selectedTagihan = this.unpaidItems.map(t => t.kode_tagihan);
                    }
                },
                toggleItem(kode) {
                    const idx = this.selectedTagihan.indexOf(kode);
                    if (idx > -1) {
                        this.selectedTagihan.splice(idx, 1);
                    } else {
                        this.selectedTagihan.push(kode);
                    }
                },
                formatRupiah(amount) {
                    return 'Rp. ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(amount);
                },
                openPayment() {
                    if (this.selectedTagihan.length === 0) return;
                    $wire.openPayModal(this.selectedTagihan);
                }
            }" class="overflow-hidden">
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
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Sisa</p>
                        <p class="font-bold text-red-600 dark:text-red-400">Rp. {{ number_format($totalSisaCard, 0, '', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-4">
                {{-- Unpaid Tagihan Section --}}
                @if(count($unpaid) > 0)
                    {{-- Select All (Admin only) --}}
                    @if($this->isAdmin())
                        <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 dark:border-gray-700">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleAll()"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:bg-gray-900"
                            />
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Pilih Semua</span>
                        </div>
                    @endif

                    <div class="space-y-2">
                        @foreach($unpaid as $tagihan)
                            @php
                                $sisa = ($tagihan['jenis_tagihan']['jumlah'] ?? 0) - ($tagihan['tmp'] ?? 0);
                                $jatuhTempo = $tagihan['jenis_tagihan']['jatuh_tempo'] ?? null;
                                $isOverdue = $jatuhTempo && \Carbon\Carbon::parse($jatuhTempo)->lt(now()->startOfDay());
                                $statusColor = $tagihan['status'] === 'Belum Lunas' ? 'warning' : 'danger';
                            @endphp
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ $isOverdue ? 'border-l-4 border-red-400' : '' }}">
                                {{-- Checkbox (Admin only) --}}
                                @if($this->isAdmin())
                                    <input
                                        type="checkbox"
                                        value="{{ $tagihan['kode_tagihan'] }}"
                                        :checked="selectedTagihan.includes('{{ $tagihan['kode_tagihan'] }}')"
                                        @change="toggleItem('{{ $tagihan['kode_tagihan'] }}')"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:bg-gray-900"
                                    />
                                @endif

                                {{-- Tagihan Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $tagihan['jenis_tagihan']['nama'] ?? '-' }}</span>
                                        @if($isOverdue)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                Jatuh Tempo
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $jatuhTempo ? \Carbon\Carbon::parse($jatuhTempo)->format('d M Y') : '-' }}
                                        · Kode: {{ $tagihan['kode_tagihan'] }}
                                    </p>
                                </div>

                                {{-- Amount Info --}}
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Rp. {{ number_format($sisa, 0, '', '.') }}</p>
                                    @if($tagihan['tmp'] > 0)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">dari Rp. {{ number_format($tagihan['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</p>
                                    @endif
                                </div>

                                {{-- Status Badge --}}
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    {{ $statusColor === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $tagihan['status'] }}
                                </span>

                                {{-- Delete Button (Admin only) --}}
                                @if($this->canDelete())
                                    <button
                                        wire:click="deleteTagihan('{{ $tagihan['kode_tagihan'] }}')"
                                        wire:confirm="Apakah kamu yakin untuk menghapus tagihan ini?"
                                        wire:loading.attr="disabled"
                                        class="text-red-400 hover:text-red-600 p-1 rounded transition"
                                        title="Hapus Tagihan"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Paid Tagihan Section --}}
                @if(count($paid) > 0)
                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Lunas</p>
                        <div class="space-y-1">
                            @foreach($paid as $tagihan)
                                <div class="flex items-center gap-3 p-2 rounded-lg opacity-60">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $tagihan['jenis_tagihan']['nama'] ?? '-' }}</span>
                                        <p class="text-xs text-gray-400">
                                            {{ isset($tagihan['jenis_tagihan']['jatuh_tempo']) ? \Carbon\Carbon::parse($tagihan['jenis_tagihan']['jatuh_tempo'])->format('d M Y') : '-' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Rp. {{ number_format($tagihan['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Lunas
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Rekap & Bayar Section (Admin only) --}}
                @if($this->isAdmin() && count($unpaid) > 0)
                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between" x-show="selectedTagihan.length > 0" x-cloak>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Rekap Pembayaran</p>
                                <p class="text-lg font-bold text-primary-600" x-text="formatRupiah(rekapTotal)"></p>
                            </div>
                            <button
                                @click="openPayment()"
                                :disabled="selectedTagihan.length === 0"
                                class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                Bayar (<span x-text="selectedTagihan.length"></span>)
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @empty
        {{-- Empty State --}}
        <x-filament::section>
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak Ada Tagihan</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if(filled($search) || filled($filterJenjang) || filled($filterStatus))
                        Tidak ada siswa yang cocok dengan filter yang diterapkan.
                    @else
                        Belum ada tagihan yang tersedia.
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
