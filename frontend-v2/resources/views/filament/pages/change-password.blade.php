<x-filament-panels::page>
    <div class="max-w-md mx-auto">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
            <div class="pt-4">
                <x-filament::button type="submit" class="w-full">
                    Ubah Password
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
