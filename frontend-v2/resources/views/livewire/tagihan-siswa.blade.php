<div class="space-y-6">
    {{-- Sibling Selector (hidden if no siblings) --}}
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

    {{-- Summary Section --}}
    @if(count($tagihanData) > 0)
        @php
            $totalTagihan = collect($tagihanData)->sum(fn($t) => $t['jenis_tagihan']['jumlah'] ?? 0);
            $totalDibayar = collect($tagihanData)->sum(fn($t) => $t['tmp'] ?? 0);
            $totalSisa = $totalTagihan - $totalDibayar;
        @endphp
        <x-filament::section>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ringkasan Tagihan — {{ $selectedSiswaName }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3">
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Tagihan</p>
                    <p class="text-lg font-bold text-blue-800 dark:text-blue-300">Rp. {{ number_format($totalTagihan, 0, '', '.') }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-3">
                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">Sudah Dibayar</p>
                    <p class="text-lg font-bold text-green-800 dark:text-green-300">Rp. {{ number_format($totalDibayar, 0, '', '.') }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-3">
                    <p class="text-xs text-red-600 dark:text-red-400 font-medium">Sisa Tagihan</p>
                    <p class="text-lg font-bold text-red-800 dark:text-red-300">Rp. {{ number_format($totalSisa, 0, '', '.') }}</p>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Tagihan Cards --}}
    @if(count($tagihanData) > 0)
        @php
            $unpaid = collect($tagihanData)->filter(fn($t) => $t['status'] !== 'Lunas')->values()->toArray();
            $paid = collect($tagihanData)->filter(fn($t) => $t['status'] === 'Lunas')->values()->toArray();

            usort($unpaid, fn($a, $b) => strcmp($a['jenis_tagihan']['jatuh_tempo'] ?? '', $b['jenis_tagihan']['jatuh_tempo'] ?? ''));
            usort($paid, fn($a, $b) => strcmp($a['jenis_tagihan']['jatuh_tempo'] ?? '', $b['jenis_tagihan']['jatuh_tempo'] ?? ''));
        @endphp

        <x-filament::section class="overflow-hidden">
            {{-- Card Header --}}
            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $selectedSiswaName }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Daftar Tagihan</p>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-4">
                {{-- Unpaid Tagihan --}}
                @if(count($unpaid) > 0)
                    <div class="space-y-2">
                        @foreach($unpaid as $tagihan)
                            @php
                                $sisa = ($tagihan['jenis_tagihan']['jumlah'] ?? 0) - ($tagihan['tmp'] ?? 0);
                                $jatuhTempo = $tagihan['jenis_tagihan']['jatuh_tempo'] ?? null;
                                $isOverdue = $jatuhTempo && \Carbon\Carbon::parse($jatuhTempo)->lt(now()->startOfDay());
                                $statusColor = $tagihan['status'] === 'Belum Lunas' ? 'warning' : 'danger';
                            @endphp
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ $isOverdue ? 'border-l-4 border-red-400' : '' }}">
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
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Paid Tagihan --}}
                @if(count($paid) > 0)
                    <div class="{{ count($unpaid) > 0 ? 'mt-4 pt-3 border-t border-gray-200 dark:border-gray-700' : '' }}">
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
            </div>
        </x-filament::section>
    @else
        {{-- Empty State --}}
        <x-filament::section>
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tidak Ada Tagihan</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada tagihan yang tersedia.</p>
            </div>
        </x-filament::section>
    @endif
</div>
