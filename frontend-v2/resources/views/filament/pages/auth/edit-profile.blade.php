<x-filament-panels::page>
    <div class="max-w-xl space-y-6">
        {{-- User Info Section --}}
        <x-filament::section>
            <x-slot name="heading">Informasi Akun</x-slot>
            <x-slot name="description">Informasi dasar akun Anda.</x-slot>

            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                        <x-heroicon-o-user class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $username ?? '-' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ is_array($roles) ? implode(', ', $roles) : '-' }}
                            @if($branchLocation)
                                — {{ $branchLocation }}
                            @endif
                        </p>
                    </div>
                </div>

                @if($currentEmail)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-envelope class="h-4 w-4" />
                        <span>{{ $currentEmail }}</span>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        <span>Email belum diatur</span>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Email Section --}}
        <x-filament::section>
            <x-slot name="heading">Email</x-slot>
            <x-slot name="description">
                Perbarui alamat email yang digunakan untuk login dan notifikasi.
            </x-slot>

            <form wire:submit="updateEmail" class="space-y-4">
                {{ $this->emailFormSchema }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="updateEmail">Simpan Email</span>
                        <span wire:loading wire:target="updateEmail">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Menyimpan...
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Password Section --}}
        <x-filament::section>
            <x-slot name="heading">Ubah Password</x-slot>
            <x-slot name="description">
                Pastikan akun Anda menggunakan password yang kuat dan unik.
            </x-slot>

            <form wire:submit="changePassword" class="space-y-4">
                {{ $this->passwordFormSchema }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="changePassword">Ubah Password</span>
                        <span wire:loading wire:target="changePassword">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Mengubah...
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
