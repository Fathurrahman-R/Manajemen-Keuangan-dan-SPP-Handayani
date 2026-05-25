<div>
    {{-- Kenaikan Kelas & Kelulusan --}}
    <div class="space-y-6">
        {{-- Period Selector --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Periode Sumber</label>
                <select wire:model.live="selectedSourcePeriodId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Pilih Periode Sumber</option>
                    @foreach($tahunAjaranOptions as $option)
                        <option value="{{ $option['id'] }}">
                            {{ $option['nama'] }} {{ $option['status'] === 'Aktif' ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Periode Tujuan</label>
                <select wire:model.live="selectedTargetPeriodId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Pilih Periode Tujuan</option>
                    @foreach($tahunAjaranOptions as $option)
                        @if($option['id'] != $selectedSourcePeriodId)
                            <option value="{{ $option['id'] }}">{{ $option['nama'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Jenjang Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                @foreach(['MI', 'TK', 'KB'] as $jenjang)
                    <button
                        wire:click="$set('activeJenjangTab', '{{ $jenjang }}')"
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeJenjangTab === $jenjang ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        {{ $jenjang }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Kelas List --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-1 space-y-2">
                <h3 class="text-sm font-medium text-gray-700">Daftar Kelas</h3>
                @forelse($kelasList as $kelas)
                    <button
                        wire:click="$set('selectedKelasId', {{ $kelas['id'] }})"
                        class="w-full text-left px-3 py-2 rounded-md text-sm {{ $selectedKelasId == $kelas['id'] ? 'bg-primary-100 text-primary-700 font-medium' : 'hover:bg-gray-100' }}"
                    >
                        {{ $kelas['nama'] }} (Level {{ $kelas['level'] }})
                    </button>
                @empty
                    <p class="text-sm text-gray-500">Tidak ada kelas dengan level yang dikonfigurasi.</p>
                @endforelse
            </div>

            {{-- Student Table --}}
            <div class="md:col-span-3">
                @if($selectedKelasId && count($students) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($students as $student)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $student['nis'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $student['nama'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <div class="flex items-center gap-2">
                                                <select
                                                    wire:change="updateStudentAction({{ $student['id'] }}, $event.target.value)"
                                                    class="rounded-md border-gray-300 text-sm"
                                                >
                                                    @foreach($this->getAvailableActions() as $value => $label)
                                                        <option value="{{ $value }}" {{ ($studentActions[$student['id']] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>

                                                {{-- Target kelas selector for pindah_jenjang --}}
                                                @if(($studentActions[$student['id']] ?? '') === 'pindah_jenjang' && count($targetJenjangKelasList) > 0)
                                                    <select
                                                        wire:change="updateStudentTargetKelas({{ $student['id'] }}, $event.target.value)"
                                                        class="rounded-md border-gray-300 text-sm"
                                                    >
                                                        <option value="">Pilih Kelas Tujuan</option>
                                                        @foreach($targetJenjangKelasList as $targetKelas)
                                                            <option value="{{ $targetKelas['id'] }}" {{ ($studentTargetKelas[$student['id']] ?? null) == $targetKelas['id'] ? 'selected' : '' }}>
                                                                {{ $targetKelas['nama'] ?? $targetKelas['name'] ?? '' }} (Level {{ $targetKelas['level'] ?? '-' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary Panel --}}
                    @if(count($summary) > 0)
                        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Ringkasan Aksi</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                <div class="p-2 bg-white rounded border">
                                    <span class="text-gray-600">Naik Kelas:</span>
                                    <span class="font-semibold text-blue-600">{{ $summary['naik_kelas'] ?? 0 }}</span>
                                </div>
                                <div class="p-2 bg-white rounded border">
                                    <span class="text-gray-600">Tinggal Kelas:</span>
                                    <span class="font-semibold text-yellow-600">{{ $summary['tinggal_kelas'] ?? 0 }}</span>
                                </div>
                                @if($isKelasTertinggi)
                                    <div class="p-2 bg-white rounded border">
                                        <span class="text-gray-600">Lulus:</span>
                                        <span class="font-semibold text-green-600">{{ $summary['lulus'] ?? 0 }}</span>
                                    </div>
                                    <div class="p-2 bg-white rounded border">
                                        <span class="text-gray-600">Pindah Jenjang:</span>
                                        <span class="font-semibold text-purple-600">{{ $summary['pindah_jenjang'] ?? 0 }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                Total siswa: <span class="font-medium">{{ array_sum($summary) }}</span>
                            </div>
                        </div>

                        {{-- Process Button --}}
                        <div class="mt-4">
                            <button
                                wire:click="processAll"
                                wire:confirm="Apakah Anda yakin ingin memproses kenaikan kelas untuk {{ array_sum($summary) }} siswa? Tindakan ini akan membuat perubahan pada data siswa."
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                                @disabled($processing || count($students) === 0 || !$selectedTargetPeriodId)
                            >
                                @if($processing)
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memproses...
                                @else
                                    Proses Kenaikan Kelas
                                @endif
                            </button>
                        </div>
                    @endif
                @elseif($selectedKelasId)
                    <p class="text-sm text-gray-500">Tidak ada siswa yang memenuhi syarat di kelas ini.</p>
                @else
                    <p class="text-sm text-gray-500">Pilih kelas untuk melihat daftar siswa.</p>
                @endif
            </div>
        </div>

        {{-- Riwayat Proses --}}
        <div class="mt-8 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Riwayat Proses</h2>

            @if(count($history) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas Asal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($history as $batch)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ isset($batch['processed_at']) ? \Carbon\Carbon::parse($batch['processed_at'])->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $this->translateBatchType($batch['batch_type'] ?? '') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $batch['kelas_nama'] ?? $batch['kelas']['nama'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <span class="text-xs">{{ $batch['source_tahun_ajaran_nama'] ?? $batch['source_tahun_ajaran']['nama'] ?? '-' }}</span>
                                        <span class="text-gray-400 mx-1">→</span>
                                        <span class="text-xs">{{ $batch['target_tahun_ajaran_nama'] ?? $batch['target_tahun_ajaran']['nama'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $batch['details_count'] ?? count($batch['details'] ?? []) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if(($batch['status'] ?? '') === 'completed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Undone
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $batch['processed_by_name'] ?? $batch['processed_by_user']['name'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm space-x-2">
                                        <button
                                            wire:click="showBatchDetail('{{ $batch['id'] }}')"
                                            class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                        >
                                            Detail
                                        </button>
                                        @if(($batch['status'] ?? '') === 'completed')
                                            <button
                                                wire:click="undoBatch('{{ $batch['id'] }}')"
                                                wire:confirm="Apakah Anda yakin ingin membatalkan batch ini? Semua perubahan yang dilakukan akan dikembalikan."
                                                class="text-red-600 hover:text-red-800 text-xs font-medium"
                                            >
                                                Undo
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">Belum ada riwayat proses kenaikan kelas.</p>
            @endif
        </div>

        {{-- Batch Detail Modal --}}
        @if($selectedBatchDetail)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    {{-- Background overlay --}}
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeBatchDetail"></div>

                    {{-- Modal panel --}}
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900" id="modal-title">
                                    Detail Batch: {{ $this->translateBatchType($selectedBatchDetail['batch_type'] ?? '') }}
                                </h3>
                                <button wire:click="closeBatchDetail" class="text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mb-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Tanggal:</span>
                                    <span class="font-medium">{{ isset($selectedBatchDetail['processed_at']) ? \Carbon\Carbon::parse($selectedBatchDetail['processed_at'])->format('d/m/Y H:i') : '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Status:</span>
                                    @if(($selectedBatchDetail['status'] ?? '') === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Completed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Undone</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-gray-500">Kelas Asal:</span>
                                    <span class="font-medium">{{ $selectedBatchDetail['kelas_nama'] ?? $selectedBatchDetail['kelas']['nama'] ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Diproses oleh:</span>
                                    <span class="font-medium">{{ $selectedBatchDetail['processed_by_name'] ?? $selectedBatchDetail['processed_by_user']['name'] ?? '-' }}</span>
                                </div>
                            </div>

                            {{-- Detail students table --}}
                            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kelas Asal</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kelas Tujuan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($selectedBatchDetail['details'] ?? [] as $detail)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $detail['siswa']['nis'] ?? $detail['siswa_nis'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $detail['siswa']['nama'] ?? $detail['siswa_nama'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-sm">
                                                    @php
                                                        $actionLabel = match($detail['action'] ?? '') {
                                                            'naik_kelas' => 'Naik Kelas',
                                                            'lulus' => 'Lulus',
                                                            'tinggal_kelas' => 'Tinggal Kelas',
                                                            'pindah_jenjang' => 'Pindah Jenjang',
                                                            default => ucfirst(str_replace('_', ' ', $detail['action'] ?? '-')),
                                                        };
                                                    @endphp
                                                    <span class="text-gray-900">{{ $actionLabel }}</span>
                                                </td>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $detail['source_kelas']['nama'] ?? $detail['source_kelas_nama'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $detail['target_kelas']['nama'] ?? $detail['target_kelas_nama'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="closeBatchDetail"
                                class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
