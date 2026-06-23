<div class="space-y-4 pb-32">
    {{-- Sibling Selector --}}
    @if($this->hasSiblings())
        <x-filament::section>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <label for="sibling-selector" class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                    Lihat tagihan untuk:
                </label>
                <select
                    id="sibling-selector"
                    wire:model.live="selectedSiswaId"
                    class="border border-gray-300 dark:border-gray-600 rounded-lg text-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-900 dark:text-gray-100 w-full sm:w-auto"
                >
                    <option value="{{ $ownerSiswaId }}">{{ $ownerSiswaName }} (Saya)</option>
                    @foreach($siblings as $sibling)
                        <option value="{{ $sibling['id'] }}">{{ $sibling['nama'] }}</option>
                    @endforeach
                </select>
            </div>
        </x-filament::section>
    @endif

    @if(count($tagihanData) > 0)
        @php
            $totalTagihan = collect($tagihanData)->sum(fn($t) => $t['jenis_tagihan']['jumlah'] ?? 0);
            $totalDibayar = collect($tagihanData)->sum(fn($t) => $t['tmp'] ?? 0);
            $totalSisa = $totalTagihan - $totalDibayar;
            $unpaid = collect($tagihanData)->filter(fn($t) => ($t['status'] ?? null) !== 'Lunas')->values()->all();
            $paid = collect($tagihanData)->filter(fn($t) => ($t['status'] ?? null) === 'Lunas')->values()->all();
            usort($unpaid, fn($a, $b) => strcmp($a['jenis_tagihan']['jatuh_tempo'] ?? '', $b['jenis_tagihan']['jatuh_tempo'] ?? ''));
            $eligibleCodes = collect($this->batchEligible)->pluck('kode_tagihan')->all();
            $allEligibleSelected = ! empty($eligibleCodes) && count(array_intersect($selectedKodeTagihan, $eligibleCodes)) === count($eligibleCodes);
        @endphp

        {{-- Header: profil + Total Sisa --}}
        <x-filament::section class="overflow-hidden p-0">
            <div class="px-4 py-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $selectedSiswaName }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Daftar Tagihan</p>
                </div>
            </div>
            <div class="bg-rose-50 dark:bg-rose-900/20 px-4 py-3 border-t border-rose-100 dark:border-rose-900/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-rose-700 dark:text-rose-300 font-medium">Total Sisa</span>
                    <span class="text-lg font-bold text-rose-700 dark:text-rose-300">Rp {{ number_format($totalSisa, 0, ',', '.') }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Pilih Semua --}}
        @if(! empty($eligibleCodes))
            <x-filament::section class="!p-0">
                <label class="flex items-center gap-3 px-4 py-3 cursor-pointer min-h-[44px]">
                    <input
                        type="checkbox"
                        wire:click="toggleSelectAll"
                        @checked($allEligibleSelected)
                        class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Pilih Semua (untuk Bayar Batch)
                    </span>
                </label>
            </x-filament::section>
        @endif

        {{-- Card list --}}
        <div class="space-y-3">
            @foreach($unpaid as $tagihan)
                @php
                    $kode = $tagihan['kode_tagihan'];
                    $sisa = ($tagihan['jenis_tagihan']['jumlah'] ?? 0) - ($tagihan['tmp'] ?? 0);
                    $jatuhTempo = $tagihan['jenis_tagihan']['jatuh_tempo'] ?? null;
                    $isOverdue = $jatuhTempo && \Carbon\Carbon::parse($jatuhTempo)->lt(now()->startOfDay());
                    $isPendingOnline = (bool) ($tagihan['midtrans_pending'] ?? false);
                    $statusColor = $tagihan['status'] === 'Belum Lunas' ? 'warning' : 'danger';
                    $eligible = $this->isMidtransEnabled() && $sisa >= 10000 && ! $isPendingOnline;
                @endphp

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 border-l-4 border-rose-500 overflow-hidden">
                    <div class="p-4 space-y-3">
                        {{-- Baris 1: nama + nominal --}}
                        <div class="flex items-start gap-3">
                            @if($eligible)
                                <label class="flex items-center justify-center min-w-[44px] min-h-[44px] -mt-1 -ml-1 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedKodeTagihan"
                                        value="{{ $kode }}"
                                        class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                </label>
                            @endif
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $tagihan['jenis_tagihan']['nama'] ?? '-' }}
                                </h4>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($sisa, 0, ',', '.') }}</p>
                                @if(($tagihan['tmp'] ?? 0) > 0)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        dari Rp {{ number_format($tagihan['jenis_tagihan']['jumlah'] ?? 0, 0, ',', '.') }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Baris 2: tanggal + badge --}}
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $jatuhTempo ? \Carbon\Carbon::parse($jatuhTempo)->format('d M Y') : '-' }}
                                · {{ $kode }}
                            </p>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        Jatuh Tempo
                                    </span>
                                @endif
                                @if($isPendingOnline)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        Menunggu Pembayaran
                                    </span>
                                @else
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' => $statusColor === 'warning',
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => $statusColor === 'danger',
                                    ])>
                                        {{ $tagihan['status'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Baris 3: tombol aksi full-width --}}
                        @if($eligible || $isPendingOnline)
                            <div class="pt-1">
                                @if($eligible)
                                    {{ ($this->payAction)([
                                        'kode_tagihan' => $kode,
                                        'tagihan_name' => $tagihan['jenis_tagihan']['nama'] ?? '-',
                                        'sisa' => $sisa,
                                    ]) }}
                                @elseif($isPendingOnline)
                                    {{ ($this->resumeAction)([
                                        'kode_tagihan' => $kode,
                                        'sisa' => $sisa,
                                    ]) }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Lunas section --}}
            @if(! empty($paid))
                <div class="pt-2 mt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 px-1">Lunas</p>
                    @foreach($paid as $tagihan)
                        <div class="bg-gray-50 dark:bg-gray-800/40 rounded-lg px-4 py-2 flex items-center justify-between gap-3 opacity-70">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $tagihan['jenis_tagihan']['nama'] ?? '-' }}</p>
                                <p class="text-xs text-gray-400">{{ $tagihan['kode_tagihan'] }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Lunas
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <x-filament::section>
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak Ada Tagihan</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada tagihan yang tersedia.</p>
            </div>
        </x-filament::section>
    @endif

    {{-- Sticky bar bayar batch --}}
    @if(count($selectedKodeTagihan) > 0)
        <div class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-lg">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($selectedKodeTagihan) }} tagihan terpilih</p>
                    <p class="text-base font-bold text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($this->selectedTotal, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex-shrink-0">
                    {{ $this->payBatchAction }}
                </div>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</div>
