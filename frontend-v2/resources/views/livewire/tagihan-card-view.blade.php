<div class="space-y-6 relative">
    {{-- Loading overlay — only for data-fetching actions (filter, search, pagination) --}}
    <div wire:loading.flex wire:target="loadData, search, filterKelas, filterStatus, filterJatuhTempoFrom, filterJatuhTempoTo, selectedTahunAjaranId, perPage, goToPage, previousPage, nextPage" class="absolute inset-0 z-10 items-center justify-center bg-white/60 dark:bg-gray-900/60 rounded-xl">
        <div class="flex flex-col items-center gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">Memuat...</p>
        </div>
    </div>

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
        {{-- Baris 1: Header bar dengan judul + action buttons --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                <x-heroicon-o-funnel class="h-4 w-4 text-gray-400" />
                <span>Filter &amp; Aksi</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($this->isAdmin())
                    {{ $this->exportPdfAction }}
                @endif
                @if($this->canCreate())
                    {{ $this->addTagihanAction }}
                @endif
            </div>
        </div>

        {{-- Baris 2: Search full width --}}
        <div class="mb-3">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau NIS siswa..."
                />
            </x-filament::input.wrapper>
        </div>

        {{-- Baris 3: Filter dropdown (Periode, Kelas, Status) dengan label di atas --}}
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

            @if(count($kelasOptions) > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kelas</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="filterKelas">
                            <option value="">Semua Kelas</option>
                            @foreach($kelasOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status Tagihan</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="Belum Dibayar">Belum Dibayar</option>
                        <option value="Belum Lunas">Belum Lunas</option>
                        <option value="Lunas">Lunas</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Baris 4: Filter jatuh tempo dengan label "Jatuh Tempo" --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Rentang Jatuh Tempo</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        wire:model.live="filterJatuhTempoFrom"
                        placeholder="Dari"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        wire:model.live="filterJatuhTempoTo"
                        placeholder="Sampai"
                    />
                </x-filament::input.wrapper>
            </div>
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

        <div wire:key="siswa-card-{{ $siswa['nis'] }}" x-data="{
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
                toggleAll() {
                    if (this.allSelected) {
                        this.selectedTagihan = [];
                    } else {
                        this.selectedTagihan = [...this.unpaidItems.map(t => t.kode_tagihan)];
                    }
                },
                toggleItem(kode) {
                    const idx = this.selectedTagihan.indexOf(kode);
                    if (idx > -1) {
                        this.selectedTagihan = this.selectedTagihan.filter(k => k !== kode);
                    } else {
                        this.selectedTagihan = [...this.selectedTagihan, kode];
                    }
                },
                formatRupiah(amount) {
                    return 'Rp. ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(amount);
                },
                openPayment() {
                    if (this.selectedTagihan.length === 0) return;
                    $wire.openPayModal([...this.selectedTagihan]);
                }
            }">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Header: Profil siswa (baris atas) --}}
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

            {{-- Header: Total Sisa (blok penuh, background merah muda) --}}
            @if($totalSisaCard > 0)
                <div class="mx-4 mb-3 px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-center justify-between">
                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">Total sisa tagihan</span>
                    <span class="text-base font-bold text-red-700 dark:text-red-300">Rp. {{ number_format($totalSisaCard, 0, '', '.') }}</span>
                </div>
            @endif

            {{-- Body --}}
            <div class="px-4 pb-4">
                {{-- Unpaid Tagihan --}}
                @if(count($unpaid) > 0)
                    {{-- Select All (Admin only) --}}
                    @if($this->isAdmin())
                        <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-700 mb-3">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleAll()"
                                class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:bg-gray-900"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Pilih semua</span>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @foreach($unpaid as $tagihan)
                            @php
                                $sisa = ($tagihan['jenis_tagihan']['jumlah'] ?? 0) - ($tagihan['tmp'] ?? 0);
                                $jatuhTempo = $tagihan['jenis_tagihan']['jatuh_tempo'] ?? null;
                                $isOverdue = $jatuhTempo && \Carbon\Carbon::parse($jatuhTempo)->lt(now()->startOfDay());
                                $statusColor = $tagihan['status'] === 'Belum Lunas' ? 'warning' : 'danger';
                            @endphp
                            <div class="border-l-4 {{ $isOverdue ? 'border-red-400' : 'border-gray-200 dark:border-gray-600' }} pl-3 py-2">
                                {{-- Row 1: Checkbox + Nama + Nominal --}}
                                <div class="flex items-start gap-3">
                                    @if($this->isAdmin())
                                        <input
                                            type="checkbox"
                                            value="{{ $tagihan['kode_tagihan'] }}"
                                            :checked="selectedTagihan.includes('{{ $tagihan['kode_tagihan'] }}')"
                                            @change="toggleItem('{{ $tagihan['kode_tagihan'] }}')"
                                            class="w-5 h-5 mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:bg-gray-900 flex-shrink-0"
                                        />
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $tagihan['jenis_tagihan']['nama'] ?? '-' }}</span>
                                            <div class="text-right flex-shrink-0">
                                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Rp. {{ number_format($sisa, 0, '', '.') }}</p>
                                                @if($tagihan['tmp'] > 0)
                                                    <p class="text-xs text-gray-400">dari Rp. {{ number_format($tagihan['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Row 2: Tanggal + Badges --}}
                                        <div class="flex items-center gap-2 flex-wrap mt-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $jatuhTempo ? \Carbon\Carbon::parse($jatuhTempo)->format('d M Y') : '-' }}
                                                · {{ $tagihan['kode_tagihan'] }}
                                            </span>
                                            <x-filament::badge :color="$statusColor" size="sm">
                                                {{ $tagihan['status'] }}
                                            </x-filament::badge>
                                            @if($isOverdue)
                                                <x-filament::badge color="danger" size="sm">
                                                    Jatuh tempo
                                                </x-filament::badge>
                                            @endif
                                        </div>

                                        {{-- Row 3: Action buttons (full-width, tap-friendly) --}}
                                        @if($this->isAdmin())
                                            <div class="flex items-center gap-2 mt-2">
                                                <button
                                                    type="button"
                                                    wire:click="cicilTagihan('{{ $tagihan['kode_tagihan'] }}')"
                                                    wire:loading.attr="disabled"
                                                    class="flex-1 inline-flex items-center justify-center gap-1.5 min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40 transition"
                                                >
                                                    <x-heroicon-o-banknotes class="w-4 h-4" />
                                                    Bayar
                                                </button>
                                                @if($this->canDelete())
                                                    <button
                                                        type="button"
                                                        wire:click="deleteTagihan('{{ $tagihan['kode_tagihan'] }}')"
                                                        wire:loading.attr="disabled"
                                                        class="inline-flex items-center justify-center gap-1.5 min-h-[44px] px-3 py-2 text-sm font-medium rounded-lg border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 bg-white dark:bg-gray-900 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                                    >
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                        Hapus
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Paid Tagihan Section --}}
                @if(count($paid) > 0)
                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Lunas</p>
                        <div class="space-y-2">
                            @foreach($paid as $tagihan)
                                <div class="border-l-4 border-green-300 dark:border-green-700 pl-3 py-2 opacity-60">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $tagihan['jenis_tagihan']['nama'] ?? '-' }}</span>
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                {{ isset($tagihan['jenis_tagihan']['jatuh_tempo']) ? \Carbon\Carbon::parse($tagihan['jenis_tagihan']['jatuh_tempo'])->format('d M Y') : '-' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Rp. {{ number_format($tagihan['jenis_tagihan']['jumlah'] ?? 0, 0, '', '.') }}</span>
                                            <x-filament::badge color="success" size="sm">Lunas</x-filament::badge>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Rekap & Bayar Batch (Admin only) --}}
                @if($this->isAdmin() && count($unpaid) > 0)
                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" x-show="selectedTagihan.length > 0" x-cloak x-transition>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Bayar lunas yang dipilih</p>
                                <p class="text-lg font-bold text-primary-600" x-text="formatRupiah(rekapTotal)"></p>
                            </div>
                            <button
                                x-on:click="openPayment()"
                                x-bind:disabled="selectedTagihan.length === 0"
                                type="button"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-primary-500 dark:hover:bg-primary-400 transition"
                            >
                                <x-heroicon-o-banknotes class="w-4 h-4" />
                                Bayar Lunas (<span x-text="selectedTagihan.length"></span>)
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        </div>
    @empty
        {{-- Empty State --}}
        <x-filament::section>
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak Ada Tagihan</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if(filled($search) || filled($filterKelas) || filled($filterStatus))
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

    @script
    <script>
        $wire.on('scroll-to-top', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
    @endscript
</div>
