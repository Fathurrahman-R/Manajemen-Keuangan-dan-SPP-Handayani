<x-filament-panels::page>
    {{-- Tabs --}}
    <div class="fi-tabs flex items-center gap-1 overflow-x-auto rounded-xl bg-gray-50 p-1 dark:bg-white/5">
        @foreach ($this->getTabs() as $key => $label)
            @php $active = $tab === $key; @endphp
            <button
                type="button"
                wire:click="setTab('{{ $key }}')"
                wire:loading.attr="disabled"
                wire:target="setTab"
                class="fi-tabs-item relative flex h-10 items-center gap-x-2 rounded-lg px-3 text-sm font-medium transition
                    {{ $active
                        ? 'bg-white text-primary-600 shadow ring-1 ring-gray-950/5 dark:bg-white/10 dark:text-primary-400'
                        : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div wire:loading.delay wire:target="setTab" class="flex justify-center py-6">
        <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
    </div>

    <div wire:loading.remove wire:target="setTab">
        {{ $this->table }}
    </div>

    @script
    <script>
        $wire.on('open-url', ({ url }) => {
            window.open(url, '_blank');
        });
    </script>
    @endscript
</x-filament-panels::page>
