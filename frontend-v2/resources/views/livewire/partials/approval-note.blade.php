<div class="p-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 space-y-2">
        <p class="text-sm text-blue-800 dark:text-blue-300">{{ $note }}</p>
        @if(!empty($approvedBy))
            <p class="text-xs text-blue-600 dark:text-blue-400">Disetujui oleh: <span class="font-medium">{{ $approvedBy }}</span></p>
        @endif
        @if(!empty($approvedAt))
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $approvedAt }}</p>
        @endif
    </div>
</div>
