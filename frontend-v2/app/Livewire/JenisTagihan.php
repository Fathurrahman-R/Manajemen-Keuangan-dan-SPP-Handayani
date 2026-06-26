<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class JenisTagihan extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;
    use HandlesApiErrors;
    use \App\Livewire\Concerns\HasPeriodFilter;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, ?string $sortColumn = null, ?string $sortDirection = null): array {
                    try {
                        $params = $this->selectedTahunAjaranId
                            ? ['tahun_ajaran_id' => $this->selectedTahunAjaranId]
                            : ['all_periods' => 1];

                        $response = ApiService::client()->get('/jenis-tagihan', $params);

                        if (!$response->ok()) {
                            $this->handleApiError($response);
                            return [];
                        }

                        return $response->collect('data')
                            ->when(filled($search), fn(Collection $data): Collection => $data->filter(fn(array $record): bool => str_contains(Str::lower($record['nama']), Str::lower($search))))
                            ->when(
                                filled($sortColumn),
                                fn(Collection $data): Collection => $data->sortBy(
                                    fn(array $record) => data_get($record, $sortColumn),
                                    SORT_REGULAR,
                                    ($sortDirection ?? 'asc') === 'desc'
                                )->values()
                            )
                            ->toArray();
                    } catch (ConnectionException $e) {
                        $this->notifyConnectionError();
                        return [];
                    } catch (\Throwable $e) {
                        $this->notifyUnexpectedError();
                        return [];
                    }
                }
            )
            ->columns([
                TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
                TextColumn::make('jatuh_tempo')->label('Jatuh Tempo')->sortable(),
                TextColumn::make('jumlah')->label('Jumlah')->sortable()->money(currency: 'Rp.', decimalPlaces: 0,),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Jenis Tagihan')
            ->emptyStateDescription('Silahkan menambahkan jenis tagihan')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordActions([
                Action::make('delete')
                    ->tooltip('Hapus Jenis Tagihan')
                    ->icon('heroicon-s-trash') // Optional icon
                    ->iconButton()
                    ->color('danger') // Optional color
                    ->visible(fn(): bool => in_array('delete-jenis-tagihan', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Jenis Tagihan')
                    ->modalDescription('Apakah kamu yakin untuk menghapus jenis tagihan ini?')
                    ->modalSubmitActionLabel('Ya')
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->delete('/jenis-tagihan/' . $record['id']);

                        if (!$response->ok()) {
                            Notification::make()
                                ->title('Jenis Tagihan Gagal Dihapus')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Jenis Tagihan Berhasil Dihapus')
                                ->success()
                                ->send();
                        }
                    })
                    ->after(function () {
                        $this->resetTable();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-jenis-tagihan', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Jenis Tagihan Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua jenis tagihan yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/jenis-tagihan/' . $record['id']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            Notification::make()->title("{$success} jenis tagihan berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                Action::make('add') // Unique name for your action
                    ->label('Tambah') // Text displayed on the button
                    ->color('primary') // Optional color
                    ->button()
                    ->visible(fn(): bool => in_array('create-jenis-tagihan', session()->get('data.permissions', [])))
                    ->modalHeading('Tambah Jenis Tagihan')
                    ->modalFooterActions(function (Action $action) {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Simpan')
                                ->color('primary')
                                ->extraAttributes([
                                    'class' => 'text-white font-semibold'
                                ]),
                            $action->getModalCancelAction()->label('Batal'),
                        ];
                    })
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Tagihan')
                            ->required(),
                        DatePicker::make('jatuh_tempo')
                            ->label('Jatuh Tempo')
                            ->native(false)
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d')
                            ->displayFormat('d-m-Y')
                            ->required(),
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $response = ApiService::client()
                            ->post('/jenis-tagihan', $data);

                        if ($response->status() != 201) {
                            Notification::make()
                                ->title('Jenis Tagihan Gagal Ditambahkan')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Jenis Tagihan Berhasil Ditambahkan')
                                ->success()
                                ->send();
                        }
                    })
                    ->extraAttributes([
                        'class' => 'text-white font-semibold',
                        'id' => 'add',
                    ]),
            ]);
    }

    public function render()
    {
        return view('livewire.jenis-tagihan');
    }
}
