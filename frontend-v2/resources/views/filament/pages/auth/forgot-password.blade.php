<x-filament-panels::page.simple>
    @if($sent)
        <div class="text-center">
            <div class="mb-4">
                <x-heroicon-o-envelope class="mx-auto h-12 w-12 text-success-500" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Email Terkirim</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Jika email terdaftar, kami telah mengirimkan link reset password. Periksa inbox Anda.
            </p>
            <div class="mt-4">
                <a href="{{ filament()->getLoginUrl() }}" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                    &larr; Kembali ke Login
                </a>
            </div>
        </div>
    @else
        <x-filament-panels::form wire:submit="submit">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full">
                Kirim Link Reset
            </x-filament::button>
        </x-filament-panels::form>

        <div class="mt-4 text-center">
            <a href="{{ filament()->getLoginUrl() }}" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                &larr; Kembali ke Login
            </a>
        </div>
    @endif
</x-filament-panels::page.simple>
