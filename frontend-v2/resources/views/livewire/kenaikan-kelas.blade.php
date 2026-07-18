<div class="space-y-6">
    {{-- Period Selectors --}}
    <x-filament::section>
        <x-slot name="heading">Pengaturan Periode</x-slot>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">Periode Sumber</label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input.select wire:model.live="selectedSourcePeriodId">
                        <option value="">Pilih Periode Sumber</option>
                        @foreach($tahunAjaranOptions as $option)
                            <option value="{{ $option['id'] }}">
                                {{ $option['nama'] }} {{ ($option['status'] ?? '') === 'Aktif' ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">Periode Tujuan</label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input.select wire:model.live="selectedTargetPeriodId">
                        <option value="">Pilih Periode Tujuan</option>
                        @foreach($tahunAjaranOptions as $option)
                            @if($option['id'] != $selectedSourcePeriodId)
                                <option value="{{ $option['id'] }}">{{ $option['nama'] }}</option>
                            @endif
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    {{-- Jenjang Tabs + Kelas Selection + Student Table --}}
    <x-filament::section>
        <x-slot name="heading">Daftar Siswa</x-slot>

        {{-- Jenjang Tabs --}}
        <div>
        <x-filament::tabs label="Jenjang tabs">
            @foreach(['MI', 'TK', 'KB'] as $jenjang)
                <x-filament::tabs.item
                    :active="$activeJenjangTab === $jenjang"
                    wire:click="$set('activeJenjangTab', '{{ $jenjang }}')"
                >
                    {{ $jenjang }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-6 relative" style="min-height: 300px;">
            {{-- Single loading overlay — centered in the grid area --}}
            <div wire:loading wire:target="selectedKelasId, activeJenjangTab" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-gray-900/70 rounded-lg" style="top: 0; bottom: 0;">
                <x-filament::loading-indicator class="h-10 w-10 text-primary-500" />
            </div>

            {{-- Kelas List --}}
            <div class="md:col-span-1">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Daftar Kelas</h3>
                <div class="space-y-1">
                    @forelse($kelasList as $kelas)
                        <button
                            wire:click="$set('selectedKelasId', {{ $kelas['id'] }})"
                            @class([
                                'w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
                                'bg-primary-50 text-primary-700 font-medium ring-1 ring-primary-200 dark:bg-primary-900/20 dark:text-primary-400 dark:ring-primary-800' => $selectedKelasId == $kelas['id'],
                                'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' => $selectedKelasId != $kelas['id'],
                            ])
                        >
                            {{ $kelas['nama'] }} <span class="text-xs text-gray-500 dark:text-gray-400">(Level {{ $kelas['level'] }})</span>
                        </button>
                    @empty
                        <div class="flex flex-col items-center justify-center py-6">
                            <x-filament::icon icon="heroicon-o-academic-cap" class="h-8 w-8 text-gray-400 mb-2" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada kelas dengan level yang dikonfigurasi.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Student Table --}}
            <div class="md:col-span-3">
                @if($selectedKelasId && count($students) > 0)
                    <div class="fi-ta-content rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800/50">
                                <tr>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NIS</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($students as $student)
                                    <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="fi-ta-cell px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $student['nis'] ?? '-' }}</td>
                                        <td class="fi-ta-cell px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $student['nama'] ?? '-' }}</td>
                                        <td class="fi-ta-cell px-4 py-3 text-sm">
                                            <div class="flex items-center gap-2">
                                                <x-filament::input.wrapper>
                                                    <x-filament::input.select
                                                        wire:change="updateStudentAction({{ $student['id'] }}, $event.target.value)"
                                                    >
                                                        @foreach($this->getAvailableActions() as $value => $label)
                                                            <option value="{{ $value }}" {{ ($studentActions[$student['id']] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </x-filament::input.select>
                                                </x-filament::input.wrapper>

                                                @if(($studentActions[$student['id']] ?? '') === 'pindah_jenjang' && count($targetJenjangKelasList) > 0)
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input.select
                                                            wire:change="updateStudentTargetKelas({{ $student['id'] }}, $event.target.value)"
                                                        >
                                                            <option value="">Pilih Kelas Tujuan</option>
                                                            @foreach($targetJenjangKelasList as $targetKelas)
                                                                <option value="{{ $targetKelas['id'] }}" {{ ($studentTargetKelas[$student['id']] ?? null) == $targetKelas['id'] ? 'selected' : '' }}>
                                                                    {{ $targetKelas['nama'] ?? '' }} (Level {{ $targetKelas['level'] ?? '-' }})
                                                                </option>
                                                            @endforeach
                                                        </x-filament::input.select>
                                                    </x-filament::input.wrapper>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary --}}
                    @if(count($summary) > 0)
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                            <x-filament::section class="!p-3">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Naik Kelas</p>
                                    <p class="text-lg font-bold text-primary-600">{{ $summary['naik_kelas'] ?? 0 }}</p>
                                </div>
                            </x-filament::section>
                            <x-filament::section class="!p-3">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Tinggal Kelas</p>
                                    <p class="text-lg font-bold text-warning-600">{{ $summary['tinggal_kelas'] ?? 0 }}</p>
                                </div>
                            </x-filament::section>
                            @if($isKelasTertinggi)
                                <x-filament::section class="!p-3">
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Lulus</p>
                                        <p class="text-lg font-bold text-success-600">{{ $summary['lulus'] ?? 0 }}</p>
                                    </div>
                                </x-filament::section>
                                <x-filament::section class="!p-3">
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Pindah Jenjang</p>
                                        <p class="text-lg font-bold text-purple-600">{{ $summary['pindah_jenjang'] ?? 0 }}</p>
                                    </div>
                                </x-filament::section>
                            @endif
                        </div>

                        {{-- Process Button --}}
                        <div class="mt-4 flex items-center gap-3">
                            {{ $this->processAction }}
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Total: {{ array_sum($summary) }} siswa
                            </span>
                        </div>
                    @endif
                @elseif($selectedKelasId)
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-filament::icon icon="heroicon-o-user-group" class="h-12 w-12 text-gray-400 mb-3" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada siswa yang memenuhi syarat di kelas ini.</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-filament::icon icon="heroicon-o-cursor-arrow-rays" class="h-12 w-12 text-gray-400 mb-3" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih kelas untuk melihat daftar siswa.</p>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- Riwayat Proses (using Filament Table) --}}
    <x-filament::section>
        <x-slot name="heading">Riwayat Proses</x-slot>
        {{ $this->table }}
    </x-filament::section>

    {{-- Filament Actions Modals (handles confirmation modal & detail modal from record actions) --}}
    <x-filament-actions::modals />
</div>
