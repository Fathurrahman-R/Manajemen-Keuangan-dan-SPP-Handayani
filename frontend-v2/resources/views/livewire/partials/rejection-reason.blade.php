<div class="p-4">
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 space-y-2">
        <p class="text-sm text-red-800 dark:text-red-300">{{ $reason }}</p>
        @if(!empty($rejectedBy))
            <p class="text-xs text-red-600 dark:text-red-400">Ditolak oleh: <span class="font-medium">{{ $rejectedBy }}</span></p>
        @endif
        @if(!empty($rejectedAt))
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rejectedAt }}</p>
        @endif
    </div>
</div>
