<?php

namespace App\Livewire;

use App\Services\ApiService;
use App\Livewire\Concerns\HasImportExport;
use Exception;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Pembayaran extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable, HasImportExport;

    public $perPage = 5;
    public $currentPage = 1;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    $this->perPage = $recordsPerPage;
                    $this->currentPage = $page;
                    $params = [
                        'per_page' => $this->perPage,
                        'page' => $page,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    if (filled($sortColumn)) {
                        $params['sort'] = $sortColumn;
                        $params['direction'] = $sortDirection ?? 'asc';
                    }

                    $response = ApiService::client()
                        ->get('/pembayaran', $params)
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
                TextColumn::make('kode_pembayaran')->label('Kode Pembayaran')->searchable(),
                TextColumn::make('tanggal')->label('Tanggal Pembayaran')->sortable(),
                TextColumn::make('pembayar')->label('Dibayar Oleh'),
                TextColumn::make('jumlah')->label('Jumlah Pembayaran')->sortable()->money(currency: 'Rp.', decimalPlaces: 0, ),
                TextColumn::make('kode_tagihan.kode_tagihan')->label('Kode Tagihan'),
                TextColumn::make('kode_tagihan.jenis_tagihan.nama')->label('Jenis Tagihan'),
                TextColumn::make('kode_tagihan.jenis_tagihan.jumlah')->label('Jumlah Tagihan')->money(currency: 'Rp.', decimalPlaces: 0, ),
                TextColumn::make('kode_tagihan.siswa.nama')->label('Nama Siswa'),
                TextColumn::make('metode')->label('Metode Pembayaran'),
            ])
            ->filters([
                SelectFilter::make('metode')
                    ->label('Metode Pembayaran')
                    ->options([
                        'Tunai' => 'Tunai',
                        'Non-Tunai' => 'Non-Tunai',
                    ]),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Pembayaran')
            ->emptyStateDescription('Silahkan menambahkan tagihan')
            ->bulkActions([
                BulkAction::make('bulkDelete')
                    ->label('Hapus Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(): bool => in_array('delete-pembayaran', session()->get('data.permissions', [])))
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Pembayaran Terpilih')
                    ->modalDescription('Apakah kamu yakin ingin menghapus semua pembayaran yang dipilih?')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;
                        foreach ($records as $record) {
                            $response = ApiService::client()->delete('/pembayaran/' . $record['kode_pembayaran']);
                            $response->ok() ? $success++ : $failed++;
                        }
                        if ($failed > 0) {
                            \Filament\Notifications\Notification::make()->title("{$success} berhasil, {$failed} gagal dihapus")->warning()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title("{$success} pembayaran berhasil dihapus")->success()->send();
                        }
                        $this->resetTable();
                        $this->deselectAllTableRecords();
                    }),
            ])
            ->headerActions([
                ...$this->makeImportExportActions('pembayaran', [
                    \Filament\Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai'),
                    \Filament\Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Tanggal Selesai'),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('receipt')
                        ->label('Kwitansi')
                        ->tooltip('Mengunduh Kwitansi')
                        ->action(function (array $data, $record): StreamedResponse {
                            $filename = 'kwitansi-' . $record['kode_pembayaran'] . '.pdf';

                            $response = ApiService::client()
                                ->withHeaders(['Accept' => 'application/pdf'])
                                ->get('/pembayaran/kwitansi/' . $record['kode_pembayaran']);

                            if (!$response->ok()) {
                                throw new Exception($response->json()['errors']['message'][0]);
                            }

                            // Store the file temporarily (optional, but good practice for larger files)
                            Storage::disk('local')->put($filename, $response->body());
                            $path = Storage::disk('local')->path($filename);

                            // Return a response that prompts the file download
                            return response()->streamDownload(function () use ($path) {
                                echo file_get_contents($path);
                                // Clean up the temporary file after streaming
                                unlink($path);
                            }, $filename, [
                                'Content-Type' => 'application/pdf', // Set the correct MIME type
                            ]);
                        })
                        ->successNotificationTitle('Kwitansi Berhasil Diunggah')
                        ->failureNotificationTitle('Kwitansi Gagal Diunggah'),
                    Action::make('delete') // Unique name for your action
                        ->label('Hapus')
                        ->tooltip('Hapus Pembayaran')
                        ->color('danger') // Optional color
                        ->visible(fn(): bool => in_array('delete-pembayaran', session()->get('data.permissions', [])))
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pembayaran')
                        ->modalDescription('Apakah kamu yakin untuk menghapus pembayaran ini?')
                        ->modalSubmitActionLabel('Ya')
                        ->modalCancelActionLabel('Batal')
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->action(function (array $data, $record): void {
                            $response = ApiService::client()
                                ->delete('/pembayaran/' . $record['kode_pembayaran']);

                            if (!$response->ok()) {
                                throw new Exception($response->json()['errors']['message'][0]);
                            }
                        })
                        ->successNotificationTitle('Pembayaran Berhasil Dihapus')
                        ->failureNotificationTitle('Pembayaran Gagal Dihapus')
                        ->after(function () {
                            $this->resetTable();
                        })
                ])
            ]);
    }

    public function render()
    {
        return view('livewire.pembayaran');
    }
}
