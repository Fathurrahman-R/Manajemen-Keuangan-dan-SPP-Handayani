<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Livewire\Component;

class DetailWali extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?int $id;
    public ?array $waliData = null;

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    public function infolistDataWali(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Text::make('Data Wali')
                        ->size(TextSize::Large)
                        ->extraAttributes([
                            'class' => 'font-bold text-2xl',
                        ]),
                    Section::make([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nama')
                                    ->label('Nama Wali')
                                    ->default(fn ($livewire) => $livewire->waliData['nama'] ?? '-')
                                    ->size(TextSize::Large),
                                TextEntry::make('hubungan')
                                    ->label('Hubungan')
                                    ->default(fn ($livewire) => $livewire->waliData['hubungan'] ?? 'Wali')
                                    ->size(TextSize::Large),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('telepon')
                                    ->label('No. Telepon')
                                    ->default(fn ($livewire) => $livewire->waliData['no_hp'] ?? '-')
                                    ->size(TextSize::Large),
                                TextEntry::make('email')
                                    ->label('Email')
                                    ->default(fn ($livewire) => $livewire->waliData['email'] ?? '-')
                                    ->size(TextSize::Large),
                            ]),
                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->default(fn ($livewire) => $livewire->waliData['alamat'] ?? '-')
                            ->columnSpanFull()
                            ->size(TextSize::Large),
                    ]),
                ]),
            ])
            ->fill($this->waliData);
    }

    public function infolistAnakTerdaftar(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Text::make('Anak yang Terdaftar')
                        ->size(TextSize::Large)
                        ->extraAttributes([
                            'class' => 'font-bold text-2xl',
                        ]),
                    Section::make([
                        RepeatableEntry::make('children')
                            ->schema([
                                TextEntry::make('nama')
                                    ->label('Nama')
                                    ->size(TextSize::Large),
                                TextEntry::make('kelas')
                                    ->label('Kelas')
                                    ->size(TextSize::Large),
                                TextEntry::make('jenjang')
                                    ->label('Jenjang')
                                    ->size(TextSize::Large),
                            ])
                            ->columns(3),
                    ]),
                ]),
            ])
            ->fill($this->waliData);
    }

    public function render()
    {
        $response = ApiService::client()
            ->get('/wali/' . $this->id);

        if (! $response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $this->waliData = $response->json()['data'];

        // Fetch children (siswa) associated with this wali
        $childrenResponse = ApiService::client()
            ->get('/wali/' . $this->id . '/siswa');

        if ($childrenResponse->ok()) {
            $this->waliData['children'] = collect($childrenResponse->json()['data'] ?? [])
                ->map(fn ($siswa) => [
                    'nama' => $siswa['nama'] ?? '-',
                    'kelas' => $siswa['kelas']['nama'] ?? $siswa['kelas'] ?? '-',
                    'jenjang' => $siswa['jenjang'] ?? '-',
                ])
                ->toArray();
        } else {
            // Fallback: use siswa relationship if included in wali response
            $this->waliData['children'] = collect($this->waliData['siswa'] ?? [])
                ->map(fn ($siswa) => [
                    'nama' => $siswa['nama'] ?? '-',
                    'kelas' => $siswa['kelas']['nama'] ?? $siswa['kelas'] ?? '-',
                    'jenjang' => $siswa['jenjang'] ?? '-',
                ])
                ->toArray();
        }

        return view('livewire.detail-wali');
    }
}
