<?php

namespace App\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Livewire\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\TextSize;

class Setting extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    public $setting;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('nama_sekolah')
                                ->label('Nama Sekolah')
                                ->size(TextSize::Large)
                                ->default($this->setting['nama_sekolah']),
                            TextEntry::make('email')
                                ->label('Email')
                                ->size(TextSize::Large)
                                ->default($this->setting['email']),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextEntry::make('alamat')
                                ->label('Alamat')
                                ->size(TextSize::Large)
                                ->default($this->setting['alamat']),
                            Grid::make(1)
                                ->schema([
                                    TextEntry::make('lokasi')
                                        ->label('Lokasi')
                                        ->size(TextSize::Large)
                                        ->default($this->setting['lokasi']),
                                    TextEntry::make('kode_pos')
                                        ->label('Kode Pos')
                                        ->size(TextSize::Large)
                                        ->default($this->setting['kode_pos']),
                                ]),
                        ]),
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('kepala_sekolah')
                                ->label('Kepala Sekolah')
                                ->size(TextSize::Large)
                                ->default($this->setting['kepala_sekolah']),
                            TextEntry::make('bendahara')
                                ->label('Bendahara')
                                ->size(TextSize::Large)
                                ->default($this->setting['bendahara']),
                        ]),
                    TextEntry::make('telepon')
                        ->label('Telepon')
                        ->size(TextSize::Large)
                        ->default($this->setting['telepon']),
                    ImageEntry::make('logo')
                        ->disk('public')
                        ->visibility('public')
                        ->default($this->setting['logo'] ? env('PHOTO_STORAGE_URL', 'http://localhost:8001/storage') . '/' . $this->setting['logo'] : url('assets/img/default.png'))
                ])
            ])
            ->fill($this->setting);
    }

    public function render()
    {
        return view('livewire.setting');
    }
}
