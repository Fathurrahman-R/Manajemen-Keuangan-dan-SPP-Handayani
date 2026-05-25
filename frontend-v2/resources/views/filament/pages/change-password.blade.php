<x-filament-panels::page>
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                    Ubah Password
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Anda harus mengubah password sebelum melanjutkan. Silakan masukkan password baru Anda.
                </p>
            </div>

            <form wire:submit="submit" class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Password Saat Ini
                    </label>
                    <input
                        type="password"
                        id="current_password"
                        wire:model="current_password"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        required
                    />
                    @error('current_password')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Password Baru
                    </label>
                    <input
                        type="password"
                        id="new_password"
                        wire:model="new_password"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        required
                        minlength="8"
                    />
                    @error('new_password')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimal 8 karakter</p>
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Konfirmasi Password Baru
                    </label>
                    <input
                        type="password"
                        id="new_password_confirmation"
                        wire:model="new_password_confirmation"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        required
                        minlength="8"
                    />
                    @error('new_password_confirmation')
                        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="submit">Ubah Password</span>
                        <span wire:loading wire:target="submit">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
