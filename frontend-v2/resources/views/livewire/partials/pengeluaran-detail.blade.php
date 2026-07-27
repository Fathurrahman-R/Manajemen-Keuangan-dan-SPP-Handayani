<div class="p-4 space-y-4">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
        <dt class="text-gray-500 dark:text-gray-400">Uraian</dt>
        <dd class="col-span-1 font-medium">{{ $record['uraian'] }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Jumlah</dt>
        <dd class="font-medium">Rp {{ number_format($record['jumlah'], 0, ',', '.') }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Tanggal Kebutuhan</dt>
        <dd class="font-medium">{{ \Carbon\Carbon::parse($record['tanggal_kebutuhan'])->format('d M Y') }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Kategori</dt>
        <dd class="font-medium">{{ $record['kategori_pengeluaran'] ?? '-' }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Pengaju</dt>
        <dd class="font-medium">{{ $record['requester']['name'] ?? $record['requester']['username'] ?? '-' }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Lampiran</dt>
        <dd class="font-medium">
            @if(!empty($record['lampiran_url']))
                <a href="{{ $record['lampiran_url'] }}" target="_blank" class="text-primary-600 dark:text-primary-400 underline">Lihat lampiran</a>
            @else
                -
            @endif
        </dd>
    </dl>

    <div>
        <h4 class="text-sm font-semibold mb-2">Riwayat Proses</h4>
        <ul class="space-y-2">
            @forelse($timeline as $step)
                <li class="text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $step['label'] }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $step['at'] }}</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mt-1">oleh {{ $step['by'] }}</p>
                    @if(!empty($step['note']))
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $step['note'] }}</p>
                    @endif
                </li>
            @empty
                <li class="text-xs text-gray-500 dark:text-gray-400">Belum ada riwayat proses.</li>
            @endforelse
        </ul>
    </div>
</div>
