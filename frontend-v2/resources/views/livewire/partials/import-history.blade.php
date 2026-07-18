<div class="space-y-2">
    @if(empty($history))
        <p class="text-sm text-gray-500 text-center py-4">Belum ada riwayat import.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">File</th>
                        <th class="px-3 py-2 text-right">Sukses</th>
                        <th class="px-3 py-2 text-right">Error</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $batch)
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="px-3 py-2">{{ $batch['file_name'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-right text-green-600">{{ $batch['success_count'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-right text-red-600">{{ $batch['error_count'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ match($batch['status'] ?? '') {
                                        'completed' => 'bg-green-100 text-green-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'rolled_back' => 'bg-gray-100 text-gray-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } }}">
                                    {{ ucfirst($batch['status'] ?? '-') }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ isset($batch['created_at']) ? \Carbon\Carbon::parse($batch['created_at'])->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-3 py-2 text-center">
                                @if(($batch['status'] ?? '') === 'completed' && isset($batch['created_at']) && \Carbon\Carbon::parse($batch['created_at'])->diffInHours(now()) < 48)
                                    <button
                                        wire:click="rollbackImport('{{ $batch['batch_reference'] }}')"
                                        wire:confirm="Yakin ingin rollback? Semua data import ini akan dihapus."
                                        class="text-xs text-red-600 hover:text-red-800 font-medium">
                                        Rollback
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
