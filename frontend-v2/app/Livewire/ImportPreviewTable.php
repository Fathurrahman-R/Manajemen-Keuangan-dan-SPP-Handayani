<?php

namespace App\Livewire;

use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Livewire\Component;

/**
 * Laporan per-baris dari sesi preview import (upload/{type} atau
 * preview/row) — dipakai sebagai child component di dalam modal Filament
 * Action "importPreviewSiswa"/"importPreviewTagihan" (lihat
 * HasImportExport::makeImportPreviewAction()).
 *
 * All-or-nothing ditegakkan di backend (SiswaImportService::confirm() /
 * TagihanImportService::confirm()); komponen ini hanya melaporkan baris
 * bermasalah dan menyediakan perbaikan inline lewat perbaikiAction(), tanpa
 * pernah menyimpan data sendiri di luar endpoint confirm.
 */
class ImportPreviewTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public string $importType;

    public string $previewId;

    /**
     * @var array<string, mixed>
     */
    public array $preview = [];

    public function mount(string $importType, string $previewId, array $preview = []): void
    {
        $this->importType = $importType;
        $this->previewId = $previewId;
        $this->preview = $preview;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                $rows = collect($this->preview['invalid_rows'] ?? []);

                if (filled($search)) {
                    $needle = mb_strtolower($search);
                    $rows = $rows->filter(function (array $row) use ($needle): bool {
                        if (str_contains(mb_strtolower((string) ($row['label'] ?? '')), $needle)) {
                            return true;
                        }

                        return collect($row['messages'] ?? [])->contains(
                            fn ($message): bool => str_contains(mb_strtolower((string) $message), $needle)
                        );
                    });
                }

                $items = $rows->values()->all();

                return new LengthAwarePaginator(
                    items: array_slice($items, ($page - 1) * $recordsPerPage, $recordsPerPage),
                    total: count($items),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('row')->label('Baris ke-')->alignCenter(),
                TextColumn::make('label')->label('Identitas')->wrap(),
                TextColumn::make('columns')->label('Kolom Bermasalah')->badge()->color('danger'),
                TextColumn::make('messages')->label('Penyebab Tidak Valid')->listWithLineBreaks()->bulleted()->wrap(),
            ])
            ->recordActions([
                $this->perbaikiAction(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->emptyStateHeading('Semua baris valid')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function perbaikiAction(): Action
    {
        return Action::make('perbaiki')
            ->label('Perbaiki')
            ->icon('heroicon-o-pencil-square')
            ->link()
            ->modalHeading(fn (array $record): string => 'Perbaiki '.($record['label'] ?? 'Baris'))
            ->modalSubmitActionLabel('Simpan & Validasi Ulang')
            ->modalCancelActionLabel('Batal')
            ->fillForm(fn (array $record): array => $record['data'] ?? [])
            ->schema(fn (array $record): array => $this->repairSchema())
            ->action(function (array $data, array $record): void {
                $response = ApiService::client()->post("/import-export/import/{$this->importType}/preview/row", [
                    'preview_id' => $this->previewId,
                    'row_index' => $record['index'],
                    'data' => $data,
                ]);

                if ($response->successful()) {
                    $this->preview = $response->json();
                    $this->resetTable();

                    $errorRows = $this->preview['error_rows'] ?? 0;
                    if ($errorRows > 0) {
                        Notification::make()
                            ->title('Baris diperbaiki')
                            ->body("Sisa {$errorRows} baris masih tidak valid.")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Semua baris valid')
                            ->body('Klik "Import Sekarang" untuk menyimpan data.')
                            ->success()
                            ->send();
                    }
                } else {
                    $errors = $response->json('errors', []);
                    Notification::make()
                        ->title('Gagal Memperbaiki Baris')
                        ->body(is_array($errors) ? implode(', ', Arr::flatten($errors)) : 'Terjadi kesalahan.')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    public function importSekarangAction(): Action
    {
        return Action::make('importSekarang')
            ->label('Import Sekarang')
            ->color('success')
            ->icon('heroicon-o-check')
            ->button()
            ->disabled(fn (): bool => ($this->preview['error_rows'] ?? 1) > 0)
            ->tooltip(fn (): ?string => ($this->preview['error_rows'] ?? 1) > 0 ? 'Masih ada baris tidak valid.' : null)
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Import')
            ->modalDescription(fn (): string => 'Simpan '.($this->preview['total_rows'] ?? 0).' data ke sistem?')
            ->modalSubmitActionLabel('Ya, Import')
            ->modalCancelActionLabel('Batal')
            ->action(function (): void {
                $response = ApiService::client()->post("/import-export/import/{$this->importType}/confirm", [
                    'preview_id' => $this->previewId,
                ]);

                if ($response->successful() || $response->status() === 202) {
                    $result = $response->json();
                    $status = $result['status'] ?? 'completed';

                    if ($status === 'processing') {
                        Notification::make()
                            ->title('Import Diproses')
                            ->body('File besar sedang diproses di background.')
                            ->info()
                            ->send();
                    } else {
                        $successCount = $result['success_count'] ?? 0;
                        Notification::make()
                            ->title('Import Berhasil')
                            ->body("{$successCount} data berhasil diimport.")
                            ->success()
                            ->send();
                    }

                    $this->dispatch('import-selesai');

                    return;
                }

                // Backend menolak lewat gerbang all-or-nothing meski tampilan
                // preview di klien sempat menunjukkan 0 error (mis. sesi basi
                // karena baris lain diubah pengguna lain). Tampilkan alasan
                // otoritatif dari backend dan segarkan tabel dari situ.
                $summary = $response->json('summary');
                $errors = $response->json('errors', []);
                Notification::make()
                    ->title('Import Gagal')
                    ->body($summary ?: (is_array($errors) ? implode(', ', Arr::flatten($errors)) : 'Konfirmasi gagal.'))
                    ->danger()
                    ->persistent()
                    ->send();

                $invalidRows = $response->json('invalid_rows');
                if (is_array($invalidRows)) {
                    $this->preview = array_merge($this->preview, [
                        'invalid_rows' => $invalidRows,
                        'error_rows' => $response->json('error_rows', count($invalidRows)),
                        'summary' => $summary,
                    ]);
                    $this->resetTable();
                }
            });
    }

    /**
     * Field perbaikan inline harus di ruang kolom Excel (nama kelas, nama
     * jenis tagihan), bukan ruang model (id) — pipeline import me-resolve
     * kelas/kategori/jenis tagihan lewat nama, bukan id. Mengirim id di sini
     * akan membuat baris tampak valid tapi diam-diam menyimpan null.
     */
    protected function repairSchema(): array
    {
        if ($this->importType === 'tagihan') {
            return [
                TextInput::make('nis')
                    ->label('NIS')
                    ->required(),
                Select::make('jenis_tagihan')
                    ->label('Jenis Tagihan')
                    ->searchable()
                    ->options(function (): array {
                        $data = ApiService::cachedGet('/jenis-tagihan', [], (int) config('handayani.cache.master_data_ttl', 300));

                        return collect($data ?? [])
                            ->mapWithKeys(fn ($item) => [$item['nama'] => $item['nama']])
                            ->toArray();
                    })
                    ->required(),
            ];
        }

        return [
            TextInput::make('nis')->label('NIS')->required(),
            TextInput::make('nisn')->label('NISN'),
            TextInput::make('nama')->label('Nama')->required(),
            Select::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan'])
                ->required(),
            TextInput::make('tempat_lahir')->label('Tempat Lahir')->required(),
            DatePicker::make('tanggal_lahir')
                ->label('Tanggal Lahir')
                ->native(false)
                ->timezone('Asia/Jakarta')
                ->format('Y-m-d')
                ->displayFormat('d-m-Y')
                ->required(),
            Select::make('agama')
                ->label('Agama')
                ->options([
                    'Islam' => 'Islam',
                    'Kristen' => 'Kristen',
                    'Katolik' => 'Katolik',
                    'Hindu' => 'Hindu',
                    'Buddha' => 'Buddha',
                    'Konghucu' => 'Konghucu',
                ])
                ->required(),
            Textarea::make('alamat')->label('Alamat')->required(),
            Select::make('jenjang')
                ->label('Jenjang')
                ->options(['TK' => 'TK', 'MI' => 'MI', 'KB' => 'KB'])
                ->live()
                ->required(),
            Select::make('kelas')
                ->label('Kelas')
                ->searchable()
                ->options(function (Get $get): array {
                    $jenjang = strtoupper((string) $get('jenjang'));
                    if ($jenjang === '') {
                        return [];
                    }

                    $data = ApiService::cachedGet('/kelas/'.$jenjang, [], (int) config('handayani.cache.master_data_ttl', 300));

                    return collect($data ?? [])
                        ->mapWithKeys(fn ($item) => [$item['nama'] => $item['nama']])
                        ->toArray();
                }),
            Select::make('kategori')
                ->label('Kategori')
                ->searchable()
                ->options(function (): array {
                    $data = ApiService::cachedGet('/kategori', [], (int) config('handayani.cache.master_data_ttl', 300));

                    return collect($data ?? [])
                        ->mapWithKeys(fn ($item) => [$item['nama'] => $item['nama']])
                        ->toArray();
                }),
            Select::make('status')
                ->label('Status')
                ->options(['Aktif' => 'Aktif', 'Lulus' => 'Lulus', 'Pindah' => 'Pindah', 'Keluar' => 'Keluar']),
            Textarea::make('keterangan_siswa')->label('Keterangan'),

            // Kolom khusus MI
            TextInput::make('asal_sekolah')->label('Asal Sekolah')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            Select::make('kelas_diterima')
                ->label('Kelas Diterima')
                ->options(['I' => 'I', 'II' => 'II', 'III' => 'III', 'IV' => 'IV', 'V' => 'V', 'VI' => 'VI'])
                ->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('tahun_diterima')->label('Tahun Diterima')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('nama_ayah')->label('Nama Ayah')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('pendidikan_terakhir_ayah')->label('Pendidikan Terakhir Ayah')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('pekerjaan_ayah')->label('Pekerjaan Ayah')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('email_ayah')->label('Email Ayah')->email()->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('nama_ibu')->label('Nama Ibu')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('pendidikan_terakhir_ibu')->label('Pendidikan Terakhir Ibu')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('pekerjaan_ibu')->label('Pekerjaan Ibu')->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),
            TextInput::make('email_ibu')->label('Email Ibu')->email()->visible(fn (Get $get): bool => $get('jenjang') === 'MI'),

            // Kolom khusus KB/TK
            TextInput::make('nama_wali')->label('Nama Wali')->visible(fn (Get $get): bool => in_array($get('jenjang'), ['KB', 'TK'], true)),
            TextInput::make('pekerjaan_wali')->label('Pekerjaan Wali')->visible(fn (Get $get): bool => in_array($get('jenjang'), ['KB', 'TK'], true)),
            TextInput::make('no_hp_wali')->label('No. HP Wali')->visible(fn (Get $get): bool => in_array($get('jenjang'), ['KB', 'TK'], true)),
            Textarea::make('alamat_wali')->label('Alamat Wali')->visible(fn (Get $get): bool => in_array($get('jenjang'), ['KB', 'TK'], true)),
            Textarea::make('keterangan_wali')->label('Keterangan Wali')->visible(fn (Get $get): bool => in_array($get('jenjang'), ['KB', 'TK'], true)),
            TextInput::make('email_wali')->label('Email Wali')->email()->visible(fn (Get $get): bool => in_array($get('jenjang'), ['KB', 'TK'], true)),
        ];
    }

    public function render()
    {
        return view('livewire.import-preview-table');
    }
}
