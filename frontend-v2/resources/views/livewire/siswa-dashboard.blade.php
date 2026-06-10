<div>
    {{-- Child Selector (for wali with multiple children) --}}
    @if(count($childOptions) > 1)
        <x-filament::section>
            <div class="flex items-center gap-3">
                <label class="text-sm text-gray-600 dark:text-gray-400">Pilih Anak:</label>
                <select wire:model.live="selectedSiswaId"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($childOptions as $child)
                        <option value="{{ $child['id'] }}">{{ $child['nama'] }} ({{ $child['nis'] }})</option>
                    @endforeach
                </select>
            </div>
        </x-filament::section>
    @endif

    {{-- Loading State --}}
    @if($loading)
        <x-filament::section>
            <div class="flex items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="ml-2 text-gray-500 dark:text-gray-400">Memuat data...</span>
            </div>
        </x-filament::section>
    @else
        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Total Tagihan --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Tagihan</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($dashboardData['total_tagihan'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Total Terbayar --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Terbayar</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($dashboardData['total_terbayar'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Total Tunggakan --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Tunggakan</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($dashboardData['total_tunggakan'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Tagihan List --}}
        <x-filament::section>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Daftar Tagihan</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <tr>
                            <th class="py-2 px-2">Jenis Tagihan</th>
                            <th class="py-2 px-2 text-right">Jumlah</th>
                            <th class="py-2 px-2">Jatuh Tempo</th>
                            <th class="py-2 px-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dashboardData['tagihan_list'] ?? [] as $item)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-2">{{ $item['nama_jenis_tagihan'] }}</td>
                                <td class="py-2 px-2 text-right">Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                                <td class="py-2 px-2">{{ $item['jatuh_tempo'] ? \Carbon\Carbon::parse($item['jatuh_tempo'])->format('d/m/Y') : '-' }}</td>
                                <td class="py-2 px-2">
                                    @php
                                        $badgeClass = match($item['status']) {
                                            'Lunas' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            'Belum Lunas' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                            default => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $badgeClass }}">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">Tidak ada tagihan</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Pembayaran Terbaru --}}
        <x-filament::section>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Pembayaran Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <tr>
                            <th class="py-2 px-2">Tanggal</th>
                            <th class="py-2 px-2">Jenis Tagihan</th>
                            <th class="py-2 px-2">Metode</th>
                            <th class="py-2 px-2 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dashboardData['pembayaran_terbaru'] ?? [] as $item)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-2">{{ \Carbon\Carbon::parse($item['tanggal'])->format('d/m/Y') }}</td>
                                <td class="py-2 px-2">{{ $item['nama_jenis_tagihan'] }}</td>
                                <td class="py-2 px-2">{{ $item['metode'] }}</td>
                                <td class="py-2 px-2 text-right font-medium text-green-600 dark:text-green-400">Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">Belum ada pembayaran</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</div>
