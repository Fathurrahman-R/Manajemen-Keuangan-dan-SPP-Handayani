<?php

namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ManajemenAkunSiswa extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public array $selectedIds = [];

    protected function hasPermission(string $permission): bool
    {
        $permissions = session()->get('data.permissions', session()->get('data')['permissions'] ?? []);
        return in_array($permission, $permissions);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn(?string $search): array => ApiService::client()
                    ->get('/akun-siswa')
                    ->collect('data')
                    ->when(filled($search), fn(Collection $data): Collection => $data->filter(
                        fn(array $record): bool => str_contains(Str::lower($record['username'] ?? ''), Str::lower($search))
                            || str_contains(Str::lower($record['name'] ?? ''), Str::lower($search))
                            || str_contains(Str::lower($record['siswa']['nama'] ?? ''), Str::lower($search))
                    ))
                    ->toArray(),
            )
            ->columns([
                TextColumn::make('username')
                    ->label('Username (NIS)'),
                TextColumn::make('name')
                    ->label('Nama'),
                TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->formatStateUsing(function ($state, $record) {
                        return $record['siswa']['nama'] ?? '-';
                    }),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => $record['is_active'] ?? false),
            ])
            ->deferLoading()
            ->striped()
            ->searchable()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Akun Siswa')
            ->emptyStateDescription('Belum ada akun siswa yang terdaftar.')
            ->recordActions([
                Action::make('resetPassword')
                    ->tooltip('Reset Password')
                    ->icon('heroicon-s-key')
                    ->iconButton()
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Apakah Anda yakin ingin mereset password akun ini ke default (tanggal lahir DDMMYYYY)?')
                    ->modalSubmitActionLabel('Reset')
                    ->action(function ($record): void {
                        $response = ApiService::client()
                            ->post('/akun-siswa/' . $record['id'] . '/reset-password');

                        if ($response->status() === 404) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Akun tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Terjadi kesalahan pada server.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Password Berhasil Direset')
                            ->body('Password telah direset ke default (tanggal lahir DDMMYYYY).')
                            ->success()
                            ->send();
                    })
                    ->after(function () {
                        $this->resetTable();
                    }),
                Action::make('toggleActive')
                    ->tooltip(fn($record) => ($record['is_active'] ?? false) ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn($record) => ($record['is_active'] ?? false) ? 'heroicon-s-x-circle' : 'heroicon-s-check-circle')
                    ->iconButton()
                    ->color(fn($record) => ($record['is_active'] ?? false) ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => ($record['is_active'] ?? false) ? 'Nonaktifkan Akun' : 'Aktifkan Akun')
                    ->modalDescription(fn($record) => ($record['is_active'] ?? false)
                        ? 'Apakah Anda yakin ingin menonaktifkan akun ini?'
                        : 'Apakah Anda yakin ingin mengaktifkan akun ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->action(function ($record): void {
                        $response = ApiService::client()
                            ->patch('/akun-siswa/' . $record['id'] . '/toggle-active');

                        if ($response->status() === 404) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Akun tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Terjadi kesalahan pada server.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $data = $response->json('data');
                        $status = ($data['is_active'] ?? false) ? 'diaktifkan' : 'dinonaktifkan';

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Akun berhasil {$status}.")
                            ->success()
                            ->send();
                    })
                    ->after(function () {
                        $this->resetTable();
                    }),
            ])
            ->headerActions([
                Action::make('viewCredentials')
                    ->label('Lihat Kredensial')
                    ->color('primaryMain')
                    ->button()
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Kredensial Akun Siswa')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (): \Illuminate\Contracts\View\View {
                        $ids = $this->selectedIds;

                        if (empty($ids)) {
                            return view('livewire.partials.credentials-empty');
                        }

                        $response = ApiService::client()
                            ->get('/akun-siswa/credentials', ['ids' => implode(',', $ids)]);

                        $credentials = $response->ok() ? $response->json('data') : [];

                        return view('livewire.partials.credentials-list', [
                            'credentials' => $credentials,
                        ]);
                    })
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                    ]),
                Action::make('printPdf')
                    ->label('Cetak PDF')
                    ->color('primaryMain')
                    ->button()
                    ->icon('heroicon-o-printer')
                    ->action(function (): void {
                        $ids = $this->selectedIds;

                        if (empty($ids)) {
                            Notification::make()
                                ->title('Perhatian')
                                ->body('Pilih akun terlebih dahulu untuk mencetak PDF.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $token = session()->get('data.token');
                        $url = env('API_URL') . '/akun-siswa/credentials/pdf?ids=' . implode(',', $ids) . '&token=' . $token;
                        $this->dispatch('open-url', url: $url);
                    })
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                    ]),
            ]);
    }

    public function toggleSelection(int $id): void
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_values(array_filter($this->selectedIds, fn($i) => $i !== $id));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function selectAll(): void
    {
        $response = ApiService::client()->get('/akun-siswa');
        $records = $response->collect('data');
        $this->selectedIds = $records->pluck('id')->toArray();
    }

    public function deselectAll(): void
    {
        $this->selectedIds = [];
    }

    public function render(): View
    {
        return view('livewire.manajemen-akun-siswa');
    }
}
