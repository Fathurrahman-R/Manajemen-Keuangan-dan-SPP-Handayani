<div>
    {{-- Bulk Akun Siswa --}}
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenjang</label>
                <select wire:model.live="filterJenjang" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Jenjang</option>
                    <option value="MI">MI</option>
                    <option value="TK">TK</option>
                    <option value="KB">KB</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Kelas</label>
                <select wire:model.live="filterKelasId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" @disabled(!$filterJenjang)>
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas['id'] }}">{{ $kelas['nama'] ?? $kelas['name'] ?? '-' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <span class="text-sm text-gray-500">
                    Total: {{ $total }} siswa belum memiliki akun
                </span>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button
                    wire:click="toggleSelectAll"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    @if($selectAll)
                        <svg class="w-4 h-4 mr-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Batal Pilih Semua
                    @else
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        Pilih Semua
                    @endif
                </button>

                @if(count($selectedSiswaIds) > 0)
                    <span class="text-sm text-gray-600">
                        {{ count($selectedSiswaIds) }} siswa dipilih
                    </span>
                @endif
            </div>

            <button
                wire:click="buatAkun"
                wire:confirm="Apakah Anda yakin ingin membuat akun untuk {{ count($selectedSiswaIds) }} siswa yang dipilih?"
                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                @disabled($processing || count($selectedSiswaIds) === 0)
            >
                @if($processing)
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memproses...
                @else
                    Buat Akun
                @endif
            </button>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAll"
                                @checked($selectAll)
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenjang</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($siswaList as $siswa)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    wire:click="toggleSiswa({{ $siswa['id'] }})"
                                    @checked(in_array($siswa['id'], $selectedSiswaIds))
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $siswa['nis'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $siswa['nama'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $siswa['jenjang'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $siswa['kelas']['nama'] ?? $siswa['kelas_nama'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                @if($filterJenjang || $filterKelasId)
                                    Tidak ada siswa tanpa akun yang sesuai dengan filter.
                                @else
                                    Semua siswa sudah memiliki akun.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($lastPage > 1)
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-700">
                    Halaman {{ $currentPage }} dari {{ $lastPage }}
                </p>
                <div class="flex gap-2">
                    <button
                        wire:click="goToPage({{ $currentPage - 1 }})"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled($currentPage <= 1)
                    >
                        Sebelumnya
                    </button>
                    <button
                        wire:click="goToPage({{ $currentPage + 1 }})"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled($currentPage >= $lastPage)
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Summary Modal --}}
    @if($showSummaryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeSummaryModal"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ count($errors) > 0 ? 'bg-yellow-100' : 'bg-green-100' }}">
                                @if(count($errors) > 0)
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="ml-4 text-lg font-medium text-gray-900" id="modal-title">
                                Hasil Pembuatan Akun
                            </h3>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <span class="text-sm text-green-700">Akun berhasil dibuat</span>
                                <span class="text-lg font-semibold text-green-700">{{ $createdCount }}</span>
                            </div>

                            @if(count($errors) > 0)
                                <div class="p-3 bg-red-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-red-700">Gagal</span>
                                        <span class="text-lg font-semibold text-red-700">{{ count($errors) }}</span>
                                    </div>
                                    <div class="mt-2 max-h-40 overflow-y-auto">
                                        <ul class="list-disc list-inside text-xs text-red-600 space-y-1">
                                            @foreach($errors as $error)
                                                <li>{{ is_array($error) ? ($error['siswa_nama'] ?? $error['siswa_id'] ?? '') . ': ' . ($error['message'] ?? 'Error') : $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            wire:click="closeSummaryModal"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
