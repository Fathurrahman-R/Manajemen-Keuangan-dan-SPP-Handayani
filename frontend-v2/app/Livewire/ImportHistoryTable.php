<?php

namespace App\Livewire;

use App\Services\ApiService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

/**
 * Riwayat import batch untuk satu import type, dipakai sebagai child component
 * di dalam modal Filament Action "Riwayat Import" (lihat HasImportExport::makeImportHistoryAction()).
 * Menggantikan tabel HTML kustom (partials/import-history.blade.php) dengan tabel Filament asli.
 */
class ImportHistoryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public string $importType;

    public function mount(string $importType): void
    {
        $this->importType = $importType;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $response = ApiService::client()->get('/import-export/import/history', [
                    'per_page' => 100,
                ]);

                if (! $response->successful()) {
                    return [];
                }

                return collect($response->json('data') ?? [])
                    ->filter(fn ($item) => ($item['import_type'] ?? '') === $this->importType)
                    ->values()
                    ->all();
            })
            ->columns([
                TextColumn::make('file_name')->label('File')->wrap(),
                TextColumn::make('success_count')->label('Sukses')->alignRight()->color('success'),
                TextColumn::make('error_count')->label('Error')->alignRight()->color('danger'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '-'))
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'failed' => 'danger',
                        'rolled_back' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->format('d/m/Y H:i') : '-'),
            ])
            ->recordActions([
                $this->rollbackAction(),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Belum ada riwayat import.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function rollbackAction(): Action
    {
        return Action::make('rollback')
            ->label('Rollback')
            ->color('danger')
            ->link()
            ->visible(fn (array $record): bool => ($record['status'] ?? '') === 'completed'
                && isset($record['created_at'])
                && Carbon::parse($record['created_at'])->diffInHours(now()) < 48
            )
            ->requiresConfirmation()
            ->modalHeading('Rollback Import')
            ->modalDescription('Yakin ingin rollback? Semua data import ini akan dihapus.')
            ->modalSubmitActionLabel('Ya, Rollback')
            ->modalCancelActionLabel('Batal')
            ->action(function (array $record): void {
                $response = ApiService::client()->post('/import-export/import/'.$record['batch_reference'].'/rollback');

                if ($response->successful()) {
                    Notification::make()
                        ->title('Rollback Berhasil')
                        ->body('Data import telah dihapus.')
                        ->success()
                        ->send();
                } else {
                    $errors = $response->json('errors', []);
                    Notification::make()
                        ->title('Rollback Gagal')
                        ->body(is_array($errors) ? implode(', ', \Illuminate\Support\Arr::flatten($errors)) : 'Rollback gagal.')
                        ->danger()
                        ->send();
                }

                $this->resetTable();
            });
    }

    public function render()
    {
        return view('livewire.import-history-table');
    }
}
