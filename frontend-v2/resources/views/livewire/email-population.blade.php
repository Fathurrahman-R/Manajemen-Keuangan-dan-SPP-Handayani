<div class="space-y-4">
    {{-- Progress Bar --}}
    @if(!empty($progress))
        <x-filament::section>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress Migrasi Email</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $progress['populated'] ?? 0 }}/{{ $progress['total'] ?? 0 }}</span>
            </div>
            @php
                $percentage = ($progress['total'] ?? 0) > 0 ? round((($progress['populated'] ?? 0) / $progress['total']) * 100) : 0;
            @endphp
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-primary-500 h-3 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
            </div>
            <p class="mt-2 text-sm {{ ($progress['complete'] ?? false) ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                {{ $progress['message'] ?? '' }}
            </p>
        </x-filament::section>
    @endif

    {{-- Filament Table --}}
    <x-filament::section class="p-0 overflow-hidden">
        {{ $this->table }}
    </x-filament::section>
</div>
