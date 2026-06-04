<x-filament-panels::page.simple>
    @if($success)
        <div class="text-center">
            <div class="mb-4">
                <x-heroicon-o-check-circle class="mx-auto h-12 w-12 text-success-500" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Password Berhasil Direset</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Silakan login dengan password baru Anda.
            </p>
            <div class="mt-4">
                <a href="{{ filament()->getLoginUrl() }}" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                    Login Sekarang &rarr;
                </a>
            </div>
        </div>
    @elseif($errorMessage && !$tokenValid)
        <div class="text-center">
            <div class="mb-4">
                <x-heroicon-o-x-circle class="mx-auto h-12 w-12 text-danger-500" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Token Tidak Valid</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ $errorMessage }}
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
                Reset Password
            </x-filament::button>
        </x-filament-panels::form>
    @endif
</x-filament-panels::page.simple>
