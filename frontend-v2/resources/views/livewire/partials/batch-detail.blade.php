<div class="space-y-4">
    @if($detail)
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Tanggal:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ isset($detail['processed_at']) ? \Carbon\Carbon::parse($detail['processed_at'])->format('d/m/Y H:i') : '-' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Status:</span>
                @if(($detail['status'] ?? '') === 'completed')
                    <x-filament::badge color="success">Completed</x-filament::badge>
                @else
                    <x-filament::badge color="gray">Undone</x-filament::badge>
                @endif
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Kelas Asal:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $detail['kelas']['nama'] ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Diproses oleh:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $detail['processed_by_user']['name'] ?? '-' }}</span>
            </div>
        </div>

        @if(!empty($detail['details']))
            <div class="border rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kelas Asal</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kelas Tujuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($detail['details'] as $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $item['siswa']['nis'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $item['siswa']['nama'] ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <x-filament::badge :color="match($item['action'] ?? '') {
                                        'naik_kelas' => 'info',
                                        'lulus' => 'success',
                                        'tinggal_kelas' => 'warning',
                                        'pindah_jenjang' => 'purple',
                                        default => 'gray',
                                    }">{{ ucfirst(str_replace('_', ' ', $item['action'] ?? '-')) }}</x-filament::badge>
                                </td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $item['source_kelas']['nama'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $item['target_kelas']['nama'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-4">Tidak ada detail.</p>
        @endif
    @else
        <p class="text-sm text-red-500 text-center py-4">Gagal memuat detail batch.</p>
    @endif
</div>
