<x-filament-panels::page>

    @if($this->setting !== null)
        @livewire('setting', ['setting' => $this->setting])
    @else
        <x-filament::section>
            <div class="text-center py-8">
                <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Data Tidak Tersedia</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pengaturan sekolah tidak dapat dimuat. Silakan coba lagi nanti.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
