<x-filament-panels::page>
    <div>
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
