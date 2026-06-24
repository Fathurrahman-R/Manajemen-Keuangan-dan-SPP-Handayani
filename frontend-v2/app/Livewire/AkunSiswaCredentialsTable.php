<?php

namespace App\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

/**
 * Tabel kredensial untuk batch lihat-kredensial di halaman Manajemen Akun Siswa.
 * Dipakai sebagai child component di dalam modal Filament Action.
 */
class AkunSiswaCredentialsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $credentials = [];

    public function mount(array $credentials = []): void
    {
        $this->credentials = $credentials;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search): LengthAwarePaginator {
                $rows = collect($this->credentials);
                if (filled($search)) {
                    $needle = mb_strtolower($search);
                    $rows = $rows->filter(fn(array $r): bool =>
                        str_contains(mb_strtolower((string) ($r['nama'] ?? '')), $needle)
                        || str_contains(mb_strtolower((string) ($r['username'] ?? '')), $needle)
                    );
                }
                $items = $rows->values()->all();

                return new LengthAwarePaginator(
                    items: $items,
                    total: count($items),
                    perPage: max(count($items), 1),
                    currentPage: 1,
                );
            })
            ->columns([
                TextColumn::make('nama')->label('Nama')->wrap(),
                TextColumn::make('username')->label('Username')->copyable()->copyMessage('Username disalin'),
                TextColumn::make('password_pattern')->label('Password Default'),
            ])
            ->striped()
            ->paginated(false)
            ->searchable(filled($this->credentials))
            ->emptyStateHeading('Tidak ada kredensial')
            ->emptyStateIcon('heroicon-o-key');
    }

    public function render()
    {
        return view('livewire.akun-siswa-credentials-table');
    }
}
