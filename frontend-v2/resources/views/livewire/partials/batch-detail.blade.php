<div class="space-y-4">
    @if($detail)
        {{-- Ringkasan info batch --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div class="flex flex-col">
                <span class="text-gray-500 dark:text-gray-400">Tanggal</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ isset($detail['processed_at']) ? \Carbon\Carbon::parse($detail['processed_at'])->format('d/m/Y H:i') : '-' }}
                </span>
            </div>
            <div class="flex flex-col">
                <span class="text-gray-500 dark:text-gray-400">Status</span>
                <div>
                    @if(($detail['status'] ?? '') === 'completed')
                        <x-filament::badge color="success">Completed</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">Undone</x-filament::badge>
                    @endif
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-gray-500 dark:text-gray-400">Kelas Asal</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $detail['kelas']['nama'] ?? $detail['kelas_nama'] ?? '-' }}
                </span>
            </div>
            <div class="flex flex-col">
                <span class="text-gray-500 dark:text-gray-400">Diproses oleh</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $detail['processed_by_user']['name'] ?? '-' }}
                </span>
            </div>
        </div>

        {{-- Tabel detail siswa (Filament Table via child Livewire component) --}}
        @livewire(
            \App\Livewire\KenaikanKelasBatchDetailTable::class,
            ['details' => $detail['details'] ?? []],
            key('batch-detail-table-' . ($detail['id'] ?? uniqid()))
        )
    @else
        <p class="text-sm text-red-500 text-center py-4">Gagal memuat detail batch.</p>
    @endif
</div>
