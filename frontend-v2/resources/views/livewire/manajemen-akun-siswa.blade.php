<div class="bg-white rounded-lg p-4 flex flex-col gap-y-4 border border-gray-200">
    {{-- Selection controls --}}
    <div class="flex items-center gap-x-3">
        <button
            wire:click="selectAll"
            class="text-sm text-primary-600 hover:text-primary-800 font-medium"
        >
            Pilih Semua
        </button>
        <button
            wire:click="deselectAll"
            class="text-sm text-gray-600 hover:text-gray-800 font-medium"
        >
            Hapus Pilihan
        </button>
        @if(count($selectedIds) > 0)
            <span class="text-sm text-gray-500">
                {{ count($selectedIds) }} akun dipilih
            </span>
        @endif
    </div>

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
