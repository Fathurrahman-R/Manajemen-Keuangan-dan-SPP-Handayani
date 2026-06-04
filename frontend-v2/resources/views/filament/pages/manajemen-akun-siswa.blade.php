<x-filament-panels::page>
    <div class="flex flex-col gap-y-4">
        {{-- Selection controls for credentials --}}
        <div class="flex items-center gap-x-3">
            <button
                wire:click="selectAll"
                class="text-sm text-primary-600 hover:text-primary-800 font-medium dark:text-primary-400 dark:hover:text-primary-300"
            >
                Pilih Semua
            </button>
            <button
                wire:click="deselectAll"
                class="text-sm text-gray-600 hover:text-gray-800 font-medium dark:text-gray-400 dark:hover:text-gray-300"
            >
                Hapus Pilihan
            </button>
            @if(count($selectedIds) > 0)
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($selectedIds) }} akun dipilih
                </span>
            @endif
        </div>

        {{-- Filament Table --}}
        <div class="w-full">
            {{ $this->table }}
        </div>
    </div>

    @script
    <script>
        $wire.on('open-url', ({ url }) => {
            window.open(url, '_blank');
        });
    </script>
    @endscript
</x-filament-panels::page>
