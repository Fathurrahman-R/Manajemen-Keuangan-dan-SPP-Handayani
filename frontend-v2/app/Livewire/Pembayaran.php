<?php

namespace App\Livewire;

use Exception;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public $perPage = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                    $params = [
                        'per_page' => $this->perPage,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    $response = Http::withHeaders([
                        'Authorization' => session()->get('data')['token']
                    ])
                        ->get(env('API_URL') . '/pembayaran', $params)
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
                TextColumn::make('tanggal')->label('Tanggal Pembayaran'),
                TextColumn::make('pembayar')->label('Dibayar Oleh'),
                TextColumn::make('jumlah')->label('Jumlah Pembayaran')->money(currency: 'Rp.', decimalPlaces: 0, ),
                TextColumn::make('kode_tagihan.kode_tagihan')->label('Kode Tagihan'),
                TextColumn::make('kode_tagihan.jenis_tagihan.nama')->label('Jenis Tagihan'),
                TextColumn::make('kode_tagihan.jenis_tagihan.jumlah')->label('Jumlah Tagihan')->money(currency: 'Rp.', decimalPlaces: 0, ),
                TextColumn::make('kode_tagihan.siswa.nama')->label('Nama Siswa'),
                TextColumn::make('metode')->label('Metode Pembayaran'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'Lunas' => 'Lunas',
                        'Belum Lunas' => 'Belum Lunas',
                        'Belum Dibayar' => 'Belum Dibayar',
                    ]),
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->multiple()
                    ->options([
                        'TK' => 'TK',
                        'SD' => 'SD',
                        'MI' => 'MI',
                    ]),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(2)
            ->paginatedWhileReordering()
            ->emptyStateHeading('Tidak Ada Pembayaran')
            ->emptyStateDescription('Silahkan menambahkan tagihan')
            ->recordActions([
                ActionGroup::make([
                    Action::make('receipt')
                        ->label('Kwitansi')
                        ->tooltip('Mengunduh Kwitansi')
                        ->action(function (array $data, $record): StreamedResponse {
                            $filename = 'kwitansi-' . $record['kode_pembayaran'] . '.pdf';

                            $response = Http::withHeaders([
                                'Authorization' => session()->get('data')['token'],
                                'Accept' => 'application/pdf'
                            ])
                                ->get(env('API_URL') . '/pembayaran/kwitansi/' . $record['kode_pembayaran']);

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
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pembayaran')
                        ->modalDescription('Apakah kamu yakin untuk menghapus pembayaran ini?')
                        ->modalSubmitActionLabel('Ya')
                        ->modalCancelActionLabel('Batal')
                        ->modalFooterActionsAlignment(Alignment::End)
                        ->action(function (array $data, $record): void {
                            $response = Http::withHeaders([
                                'Authorization' => session()->get('data')['token']
                            ])
                                ->delete(env('API_URL') . '/pembayaran/' . $record['kode_pembayaran']);

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
