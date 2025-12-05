<?php

namespace App\Livewire;

use Exception;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DetailSiswa extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?string $jenjang;
    public ?int $id;
    public ?array $siswa;

    public function infolistSiswa(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Text::make('Detail Siswa')
                        ->size(TextSize::Large)
                        ->extraAttributes([
                            'class' => 'font-bold text-2xl'
                        ]),
                    Section::make([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nis')
                                    ->label('NIS')
                                    ->default(fn($livewire) => $livewire->nis)
                                    ->size(TextSize::Large),
                                TextEntry::make('nisn')
                                    ->label('NISN')
                                    ->default(fn($livewire) => $livewire->nisn ?? '-')
                                    ->size(TextSize::Large),
                            ]),
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->default(fn($livewire) => $livewire->nama)
                            ->size(TextSize::Large),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('agama')
                                    ->label('Agama')
                                    ->default(fn($livewire) => $livewire->agama)
                                    ->size(TextSize::Large),
                                TextEntry::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->default(fn($livewire) => $livewire->jenis_kelamin)
                                    ->size(TextSize::Large),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('tempat_lahir')
                                    ->label('Tempat Lahir')
                                    ->default(fn($livewire) => $livewire->tempat_lahir)
                                    ->size(TextSize::Large),
                                TextEntry::make('tanggal_lahir')
                                    ->label('Tanggal Lahir')
                                    ->default(fn($livewire) => $livewire->tanggal_lahir)
                                    ->size(TextSize::Large),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('kelas')
                                    ->label('Kelas')
                                    ->default(fn($livewire) => $livewire->kelas['nama'])
                                    ->size(TextSize::Large),
                                TextEntry::make('kategori')
                                    ->label('Kategori')
                                    ->default(fn($livewire) => $livewire->kategori['nama'])
                                    ->size(TextSize::Large),
                            ]),
                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->default(fn($livewire) => $livewire->alamat)
                            ->size(TextSize::Large),
                        TextEntry::make('asal_sekolah')
                            ->label('Asal Sekolah')
                            ->default(fn($livewire) => $livewire->asal_sekolah)
                            ->hidden(fn($livewire) => $livewire->jenjang !== 'MI')
                            ->size(TextSize::Large),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('kelas_diterima')
                                    ->label('Kelas Diterima')
                                    ->default(fn($livewire) => $livewire->kelas_diterima)
                                    ->size(TextSize::Large),
                                TextEntry::make('tahun_diterima')
                                    ->label('Tahun Diterima')
                                    ->default(fn($livewire) => $livewire->tahun_diterima)
                                    ->size(TextSize::Large),
                            ])
                            ->hidden(fn($livewire) => $livewire->jenjang !== 'MI'),
                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->default(fn($livewire) => $livewire->keterangan ?? '-')
                            ->size(TextSize::Large),
                        TextEntry::make('status')
                            ->label('Status')
                            ->default(fn($livewire) => $livewire->status)
                            ->size(TextSize::Large),
                    ])
                ])
            ])
            ->fill($this->siswa);
    }

    public function infolistWali(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Text::make('Detail Wali')
                        ->size(TextSize::Large)
                        ->extraAttributes([
                            'class' => 'font-bold text-2xl'
                        ]),
                    Section::make([
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->default(fn($livewire) => $livewire->wali['nama'])
                            ->size(TextSize::Large),
                        TextEntry::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->default(fn($livewire) => $livewire->wali['pekerjaan'])
                            ->size(TextSize::Large),
                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->default(fn($livewire) => $livewire->wali['alamat'])
                            ->size(TextSize::Large),
                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->default(fn($livewire) => $livewire->wali['keterangan'] ?? '-')
                            ->size(TextSize::Large),
                    ])
                ])
            ])
            ->fill($this->siswa);
    }

    public function infolistAyah(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Text::make('Detail Ayah')
                        ->size(TextSize::Large)
                        ->extraAttributes([
                            'class' => 'font-bold text-2xl'
                        ]),
                    Section::make([
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->default(fn($livewire) => $livewire->ayah['nama'] ?? '-')
                            ->size(TextSize::Large),
                        TextEntry::make('pendidikan_terakhir')
                            ->label('Pendidikan Terakhir')
                            ->default(fn($livewire) => $livewire->ayah['pendidikan_terakhir'] ?? '-')
                            ->size(TextSize::Large),
                        TextEntry::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->default(fn($livewire) => $livewire->ayah['pekerjaan'] ?? '-')
                            ->size(TextSize::Large),
                    ])
                ])
            ])
            ->fill($this->siswa);
    }

    public function infolistIbu(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Text::make('Detail Ibu')
                        ->size(TextSize::Large)
                        ->extraAttributes([
                            'class' => 'font-bold text-2xl'
                        ]),
                    Section::make([
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->default(fn($livewire) => $livewire->ibu['nama'] ?? '-')
                            ->size(TextSize::Large),
                        TextEntry::make('pendidikan_terakhir')
                            ->label('Pendidikan Terakhir')
                            ->default(fn($livewire) => $livewire->ibu['pendidikan_terakhir'] ?? '-')
                            ->size(TextSize::Large),
                        TextEntry::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->default(fn($livewire) => $livewire->ibu['pekerjaan'] ?? '-')
                            ->size(TextSize::Large),
                    ])
                ])
            ])
            ->fill($this->siswa);
    }

    public function render()
    {
        $response = Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
            ->get(env('API_URL') . '/siswa/' . Str::upper($this->jenjang) . '/' . $this->id);

        if (!$response->ok()) {
            throw new Exception($response->json()['errors']['message'][0]);
        }

        $this->siswa = $response->json()['data'];

        // dd($this->siswa);

        return view('livewire.detail-siswa');
    }
}
