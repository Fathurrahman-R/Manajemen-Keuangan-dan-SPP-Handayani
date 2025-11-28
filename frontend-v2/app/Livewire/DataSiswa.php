<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DataSiswa extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $activeTab = 'TK';
    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
        ->records(
            function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                $skip = ($page - 1) * $recordsPerPage;
                
                $params = [
                    'per_page' => $this->perPage,
                    'page' => $page
                ];
                
                if (filled($search)) {
                    $params['search'] = $search;
                }

                $response = Http::withHeaders([
                    'Authorization' => session()->get('data')['token']
                ])
                ->get(env('API_URL') . '/siswa/' . $this->activeTab, $params)
                ->collect();

                return new LengthAwarePaginator(
                    items: $response['data'] ?? [],
                    total: $response['meta']['total'] ?? 0,
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            } 
        )
            ->columns([
                TextColumn::make('nis')->label('NIS')->searchable(),
                TextColumn::make('nisn')->label('NISN')->searchable()->visible($this->activeTab == 'MI')->hidden($this->activeTab != 'MI'),
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('jenis_kelamin')->label('Jenis Kelamin')->searchable(),
                TextColumn::make('tanggal_lahir')->label('Tanggal Lahir')->searchable(),
                TextColumn::make('agama')->label('Agama')->searchable(),
                TextColumn::make('wali.nama')->label('Wali')->searchable(),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Siswa')
            ->emptyStateDescription('Silahkan menambahkan siswa')
            ->recordActions([
                Action::make('update') // Unique name for your action
                    ->tooltip('Edit Siswa')
                    ->icon('heroicon-s-pencil-square') // Optional icon
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading('Edit Siswa')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalSubmitAction()
                    ->fillForm(fn(array $record): array => [
                        'id' => $record['id'],
                        'nama' => $record['nama']
                    ])
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Siswa')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->put(env('API_URL') . '/siswa/' . $record['id'], $data);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Diubah')
                    ->after(function () {
                        $this->resetTable();
                    }), // Optional color
                Action::make('delete') // Unique name for your action
                    ->tooltip('Delete Siswa')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Siswa')
                    ->modalDescription('Apakah kamu yakin untuk menghapus siswa ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->delete(env('API_URL') . '/siswa' . '/' . $record['id']);

                        if (!$response->ok()) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Dihapus')
                    ->failureNotificationTitle('Siswa Gagal Dihapus')
                    ->after(function () {
                        $this->resetTable();
                    })
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primaryMain') // Optional color
                    ->button()
                    ->modalHeading('Tambah Siswa')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                            ->label('Simpan')
                            ->color('primaryMain')
                            ->extraAttributes([
                                'class' => 'text-white font-semibold'
                            ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Siswa')
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = Http::withHeaders([
                            'Authorization' => session()->get('data')['token']
                        ])
                            ->post(env('API_URL') . '/siswa/' . $this->activeTab, $data);

                        if ($response->status() != 201) {
                            throw new Exception($response->json()['errors']['message'][0]);
                        }
                    })
                    ->successNotificationTitle('Siswa Berhasil Ditambah')
                    ->extraAttributes([
                        'class' => 'font-semibold text-white'
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.data-siswa');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }
}
