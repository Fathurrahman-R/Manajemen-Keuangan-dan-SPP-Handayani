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
                        <span>Email Siswa: {{ $currentEmail }}</span>
                        @if(in_array('siswa', $roles ?? []) && !$emailVerified)
                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/20">Belum Diverifikasi</span>
                        @elseif(in_array('siswa', $roles ?? []))
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/20">Terverifikasi</span>
                        @endif
                    </div>
                @else
                    <div class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        <span>Email Siswa belum diatur</span>
                    </div>
                @endif

                @if(in_array('siswa', $roles ?? []))
                    @if($ayahEmail)
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-heroicon-o-envelope class="h-4 w-4 shrink-0" />
                                <span class="truncate max-w-[160px] sm:max-w-none">Email Ayah: {{ $ayahEmail }}</span>
                                @if($ayahEmailVerified)
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/20">Terverifikasi</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/20">Belum Diverifikasi</span>
                                @endif
                            </div>
                            @if(!$ayahEmailVerified)
                                <div class="mt-1">
                                    <button type="button" wire:click="sendParentOtp('ayah')" wire:loading.attr="disabled" class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                        <span wire:loading.remove wire:target="sendParentOtp('ayah')">Verifikasi</span>
                                        <span wire:loading wire:target="sendParentOtp('ayah')">Mengirim...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                    @if($ibuEmail)
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-heroicon-o-envelope class="h-4 w-4 shrink-0" />
                                <span class="truncate max-w-[160px] sm:max-w-none">Email Ibu: {{ $ibuEmail }}</span>
                                @if($ibuEmailVerified)
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/20">Terverifikasi</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/20">Belum Diverifikasi</span>
                                @endif
                            </div>
                            @if(!$ibuEmailVerified)
                                <div class="mt-1">
                                    <button type="button" wire:click="sendParentOtp('ibu')" wire:loading.attr="disabled" class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                        <span wire:loading.remove wire:target="sendParentOtp('ibu')">Verifikasi</span>
                                        <span wire:loading wire:target="sendParentOtp('ibu')">Mengirim...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                    @if($waliEmail)
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-heroicon-o-envelope class="h-4 w-4 shrink-0" />
                                <span class="truncate max-w-[160px] sm:max-w-none">Email Wali: {{ $waliEmail }}</span>
                                @if($waliEmailVerified)
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/20">Terverifikasi</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/20">Belum Diverifikasi</span>
                                @endif
                            </div>
                            @if(!$waliEmailVerified)
                                <div class="mt-1">
                                    <button type="button" wire:click="sendParentOtp('wali')" wire:loading.attr="disabled" class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                        <span wire:loading.remove wire:target="sendParentOtp('wali')">Verifikasi</span>
                                        <span wire:loading wire:target="sendParentOtp('wali')">Mengirim...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </x-filament::section>

        {{-- Data Siswa Section --}}
        @if(!empty($siswaDetail) && !empty($siswaDetail['siswa']))
            @php $s = $siswaDetail['siswa']; @endphp
            <x-filament::section>
                <x-slot name="heading">Data Siswa</x-slot>
                <x-slot name="description">Informasi lengkap data diri siswa.</x-slot>

                <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <x-info-field label="NIS" :value="$s['nis'] ?? '-'"/>
                    <x-info-field label="NISN" :value="$s['nisn'] ?? '-'"/>
                    <x-info-field label="Nama Lengkap" :value="$s['nama'] ?? '-'"/>
                    <x-info-field label="Jenis Kelamin" :value="$s['jenis_kelamin'] ?? '-'"/>
                    <x-info-field label="Tempat Lahir" :value="$s['tempat_lahir'] ?? '-'"/>
                    <x-info-field label="Tanggal Lahir" :value="$s['tanggal_lahir'] ?? '-'"/>
                    <x-info-field label="Agama" :value="$s['agama'] ?? '-'"/>
                    <x-info-field label="Alamat" :value="$s['alamat'] ?? '-'"/>
                    <x-info-field label="Jenjang" :value="$s['jenjang'] ?? '-'"/>
                    <x-info-field label="Kelas" :value="$s['kelas']['nama'] ?? '-'"/>
                    <x-info-field label="Asal Sekolah" :value="$s['asal_sekolah'] ?? '-'"/>
                    <x-info-field label="Tahun Diterima" :value="$s['tahun_diterima'] ?? '-'"/>
                    <x-info-field label="Kelas Diterima" :value="$s['kelas_diterima'] ?? '-'"/>
                    <x-info-field label="Status" :value="$s['status'] ?? '-'"/>
                    @if(!empty($s['keterangan']))
                        <x-info-field label="Keterangan" :value="$s['keterangan']" class="sm:col-span-2"/>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Data Orang Tua Section (conditional per jenjang) --}}
        @if(!empty($siswaDetail))
            @php $j = $siswaDetail['siswa']['jenjang'] ?? null; @endphp

            @if($j === 'MI' && (!empty($siswaDetail['ayah']) || !empty($siswaDetail['ibu'])))
            <x-filament::section>
                <x-slot name="heading">Data Orang Tua</x-slot>
                <x-slot name="description">Informasi Ayah dan Ibu siswa.</x-slot>

                <div class="space-y-6">
                    @if(!empty($siswaDetail['ayah']))
                        @php $a = $siswaDetail['ayah']; @endphp
                        <div>
                            <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Ayah</h4>
                            <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                <x-info-field label="Nama" :value="$a['nama'] ?? '-'"/>
                                <x-info-field label="Pendidikan" :value="$a['pendidikan_terakhir'] ?? '-'"/>
                                <x-info-field label="Pekerjaan" :value="$a['pekerjaan'] ?? '-'"/>
                                <x-info-field label="Email" :value="$a['email'] ?? '-'"/>
                            </div>
                        </div>
                    @endif

                    @if(!empty($siswaDetail['ibu']))
                        @php $i = $siswaDetail['ibu']; @endphp
                        <div>
                            <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Ibu</h4>
                            <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                <x-info-field label="Nama" :value="$i['nama'] ?? '-'"/>
                                <x-info-field label="Pendidikan" :value="$i['pendidikan_terakhir'] ?? '-'"/>
                                <x-info-field label="Pekerjaan" :value="$i['pekerjaan'] ?? '-'"/>
                                <x-info-field label="Email" :value="$i['email'] ?? '-'"/>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
            @endif

            @if(in_array($j, ['TK', 'KB']) && !empty($siswaDetail['wali']))
            <x-filament::section>
                <x-slot name="heading">Data Wali</x-slot>
                <x-slot name="description">Informasi wali siswa.</x-slot>

                @php $w = $siswaDetail['wali']; @endphp
                <div class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                    <x-info-field label="Nama" :value="$w['nama'] ?? '-'"/>
                    <x-info-field label="Jenis Kelamin" :value="$w['jenis_kelamin'] ?? '-'"/>
                    <x-info-field label="Agama" :value="$w['agama'] ?? '-'"/>
                    <x-info-field label="Pendidikan" :value="$w['pendidikan_terakhir'] ?? '-'"/>
                    <x-info-field label="Pekerjaan" :value="$w['pekerjaan'] ?? '-'"/>
                    <x-info-field label="Alamat" :value="$w['alamat'] ?? '-'"/>
                    <x-info-field label="No. HP" :value="$w['no_hp'] ?? '-'"/>
                    <x-info-field label="Email" :value="$w['email'] ?? '-'"/>
                </div>
            </x-filament::section>
            @endif
        @endif

        {{-- Email Section --}}
        <x-filament::section>
            <x-slot name="heading">Email</x-slot>
            <x-slot name="description">Perbarui alamat email Anda.</x-slot>

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

        {{-- Preferensi Notifikasi Section --}}
        <x-filament::section>
            <x-slot name="heading">Preferensi Notifikasi Email</x-slot>
            <x-slot name="description">Atur jenis notifikasi apa saja yang ingin Anda terima.</x-slot>

            @if($currentEmail)
                <form wire:submit="updateNotificationPreferences" class="space-y-4">
                    {{ $this->notificationFormSchema }}

                    <div class="flex justify-end">
                        <x-filament::button type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updateNotificationPreferences">Simpan Preferensi</span>
                            <span wire:loading wire:target="updateNotificationPreferences">
                                <x-filament::loading-indicator class="h-4 w-4" />
                                Menyimpan...
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            @else
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Anda harus mengatur alamat email terlebih dahulu untuk mengelola preferensi notifikasi.
                </div>
            @endif
        </x-filament::section>

        {{-- Password Section --}}
        <x-filament::section>
            <x-slot name="heading">Ubah Password</x-slot>
            <x-slot name="description">Pastikan akun Anda menggunakan password yang kuat.</x-slot>

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

        {{-- Parent Email OTP Verification Modal --}}
        @if($showParentOtpModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="cancelParentOtp">
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                        Verifikasi Email {{ match($parentOtpType) { 'ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali', default => '' } }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Kode OTP telah dikirim ke email {{ match($parentOtpType) { 'ayah' => $ayahEmail, 'ibu' => $ibuEmail, 'wali' => $waliEmail, default => '' } }}. Masukkan kode 6 digit untuk memverifikasi.
                    </p>

                    <form wire:submit="verifyParentOtp" class="mt-4 space-y-4">
                        <div>
                            <label for="parentOtp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kode OTP</label>
                            <input
                                type="text"
                                id="parentOtp"
                                wire:model="parentOtp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="000000"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-center text-2xl font-mono tracking-[0.5em] shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            @error('parentOtp')
                                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <x-filament::button color="gray" type="button" wire:click="cancelParentOtp">
                                Batal
                            </x-filament::button>
                            <x-filament::button type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="verifyParentOtp">Verifikasi</span>
                                <span wire:loading wire:target="verifyParentOtp">
                                    <x-filament::loading-indicator class="h-4 w-4" />
                                    Memverifikasi...
                                </span>
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
