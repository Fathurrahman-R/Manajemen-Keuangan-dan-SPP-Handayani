<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Alignment;
use UnitEnum;
use Filament\Forms\Components\FileUpload as FileUploadComponent;
use Filament\Notifications\Notification;
use Filament\QueryBuilder\Constraints\Operators\IsFilledOperator;
use Illuminate\Support\Facades\Http;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static ?string $title = 'Pengaturan';

    protected static ?string $slug = 'setting';

    public ?array $setting;

    public function mount()
    {
        $response = Http::withHeaders([
            'Authorization' => session()->get('data')['token']
        ])
            ->get(env('API_URL') . '/setting');

        if ($response->successful()) {
            $this->setting = $response->json()['data'];
        } else {
            Notification::make()
                ->title('Data Sekolah Gagal Diambil')
                ->danger()
                ->send();
        }
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('updateSetting')
                ->icon('heroicon-o-pencil-square')
                ->label('Ubah')
                ->color('primaryMain')
                ->modal()
                ->fillForm(fn(): array => [
                    'id' => $this->setting['id'],
                    'nama_sekolah' => $this->setting['nama_sekolah'],
                    'lokasi' => $this->setting['lokasi'],
                    'email' => $this->setting['email'],
                    'telepon' => $this->setting['telepon'],
                    'kepala_sekolah' => $this->setting['kepala_sekolah'],
                    'bendahara' => $this->setting['bendahara'],
                    'kode_pos' => $this->setting['kode_pos'],
                    'alamat' => $this->setting['alamat'],
                    'logo' => $this->setting['logo'],
                ])
                ->modalHeading('Ubah Kategori')
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
                ->modalSubmitAction()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('nama_sekolah')
                                ->label('Nama Sekolah')
                                ->required(),
                            TextInput::make('email')
                                ->label('Email')
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Textarea::make('alamat')
                                ->label('Alamat')
                                ->rows(5)
                                ->required(),
                            Grid::make(1)
                                ->schema([
                                    TextInput::make('lokasi')
                                        ->label('Lokasi')
                                        ->required(),
                                    TextInput::make('kode_pos')
                                        ->label('Kode Pos')
                                        ->required(),
                                ]),
                        ]),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('kepala_sekolah')
                                ->label('Kepala Sekolah')
                                ->required(),
                            TextInput::make('bendahara')
                                ->label('Bendahara')
                                ->required(),
                        ]),
                    TextInput::make('telepon')
                        ->label('Telepon')
                        ->required(),
                    FileUpload::make('logo')
                        ->label('Logo')
                        ->disk('public')
                        ->visibility('public')
                        ->image()
                        ->moveFiles()
                        ->storeFiles(false)
                ])
                ->action(function (array $data, $record): void {
                    $photo = null;
                    $originalName = null;

                    if ($data['logo']) {
                        $logoName = $data['logo']->getFilename();
                        $originalName = $data['logo']->getClientOriginalName();
                        $logoPath = $data['logo']->getPath();
                        $photo = file_get_contents($logoPath . '/' . $logoName, 'r');
                    }

                    unset($data['logo']);

                    $request = Http::withHeaders([
                        'Authorization' => session()->get('data')['token'],
                    ]);

                    if (filled($photo)) {
                        $request = $request->attach('logo', $photo, $originalName);
                    }

                    $response = $request
                        ->post(env('API_URL') . '/setting/' . $this->setting['id'], $data);

                    if (!$response->successful()) {
                        Notification::make()
                            ->title('Pengaturan Gagal Diubah')
                            ->danger()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Pengaturan Berhasil Diubah')
                            ->success()
                            ->send();
                    }
                })
                ->extraAttributes([
                    'class' => 'font-semibold text-white',
                    'id' => 'update'
                ], true)
        ];
    }
}
