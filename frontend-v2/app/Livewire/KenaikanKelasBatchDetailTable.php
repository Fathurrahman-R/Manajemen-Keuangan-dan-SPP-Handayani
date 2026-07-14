<?php

namespace App\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

/**
 * Tabel detail siswa untuk satu batch kenaikan kelas.
 *
 * Dipakai sebagai child component di dalam modal Filament Action `detail` pada
 * halaman Kenaikan Kelas. Disediakan terpisah supaya bisa memakai Filament Table
 * (yang membutuhkan kelas Livewire yang implements HasTable).
 */
class KenaikanKelasBatchDetailTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $details = [];

    public function mount(array $details = []): void
    {
        $this->details = $details;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?string $search): LengthAwarePaginator {
                $collection = collect($this->details);

                if (filled($search)) {
                    $needle = mb_strtolower($search);
                    $collection = $collection->filter(function (array $row) use ($needle): bool {
                        return str_contains(mb_strtolower((string) ($row['siswa']['nis'] ?? $row['siswa_nis'] ?? '')), $needle)
                            || str_contains(mb_strtolower((string) ($row['siswa']['nama'] ?? $row['siswa_nama'] ?? '')), $needle);
                    });
                }

                $items = $collection->values()->all();

                // Tabel ini berukuran kecil dan modalnya scrollable, cukup satu halaman.
                return new LengthAwarePaginator(
                    items: $items,
                    total: count($items),
                    perPage: max(count($items), 1),
                    currentPage: 1,
                );
            })
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->state(fn (array $record): string => $record['siswa']['nis'] ?? $record['siswa_nis'] ?? '-'),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->state(fn (array $record): string => $record['siswa']['nama'] ?? $record['siswa_nama'] ?? '-'),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->state(fn (array $record): string => match ($record['action'] ?? '') {
                        'naik_kelas' => 'Naik Kelas',
                        'lulus' => 'Lulus',
                        'tinggal_kelas' => 'Tinggal Kelas',
                        'pindah_jenjang' => 'Pindah Jenjang',
                        default => ucfirst(str_replace('_', ' ', (string) ($record['action'] ?? '-'))),
                    })
                    ->color(fn (array $record) => match ($record['action'] ?? '') {
                        'naik_kelas' => 'info',
                        'lulus' => 'success',
                        'tinggal_kelas' => 'warning',
                        'pindah_jenjang' => Color::Purple,
                        default => 'gray',
                    }),
                TextColumn::make('source_kelas')
                    ->label('Kelas Asal')
                    ->state(fn (array $record): string => $record['source_kelas']['nama'] ?? $record['source_kelas_nama'] ?? '-'),
                TextColumn::make('target_kelas')
                    ->label('Kelas Tujuan')
                    ->state(fn (array $record): string => $record['target_kelas']['nama'] ?? $record['target_kelas_nama'] ?? '-'),
            ])
            ->striped()
            ->paginated(false)
            ->searchable(filled($this->details))
            ->emptyStateHeading('Tidak ada detail siswa')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    public function render()
    {
        return view('livewire.kenaikan-kelas-batch-detail-table');
    }
}
