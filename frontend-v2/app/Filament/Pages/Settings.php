<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\TextSize;
use Illuminate\Http\Client\ConnectionException;

class Settings extends Page
{
    /** Base storage URL — derived from API_URL */
    private const STORAGE_URL = 'http://127.0.0.1:8080/storage';

    use HandlesApiErrors;
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.settings';

    protected static ?string $title = 'Pengaturan';

    protected static ?string $slug = 'setting';

    public ?array $setting;

    public function mount()
    {
        abort_if(! PermissionHelper::hasResource('pengaturan.view'), 403);

        $this->loadSetting();
    }

    protected function loadSetting(): void
    {
        try {
            $response = ApiService::client()->get('/setting');

            if ($response->ok()) {
                $this->setting = $response->json('data');
            } else {
                $this->handleApiError($response);
                $this->setting = null;
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
            $this->setting = null;
        }
    }

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
                                ->state(fn (): ?string => $this->setting['nama_sekolah'] ?? null),
                            TextEntry::make('email')
                                ->label('Email')
                                ->size(TextSize::Large)
                                ->state(fn (): ?string => $this->setting['email'] ?? null),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextEntry::make('alamat')
                                ->label('Alamat')
                                ->size(TextSize::Large)
                                ->state(fn (): ?string => $this->setting['alamat'] ?? null),
                            Grid::make(1)
                                ->schema([
                                    TextEntry::make('lokasi')
                                        ->label('Lokasi')
                                        ->size(TextSize::Large)
                                        ->state(fn (): ?string => $this->setting['lokasi'] ?? null),
                                    TextEntry::make('kode_pos')
                                        ->label('Kode Pos')
                                        ->size(TextSize::Large)
                                        ->state(fn (): ?string => $this->setting['kode_pos'] ?? null),
                                ]),
                        ]),
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('kepala_sekolah')
                                ->label('Kepala Sekolah')
                                ->size(TextSize::Large)
                                ->state(fn (): ?string => $this->setting['kepala_sekolah'] ?? null),
                            TextEntry::make('bendahara')
                                ->label('Bendahara')
                                ->size(TextSize::Large)
                                ->state(fn (): ?string => $this->setting['bendahara'] ?? null),
                        ]),
                    TextEntry::make('telepon')
                        ->label('Telepon')
                        ->size(TextSize::Large)
                        ->state(fn (): ?string => $this->setting['telepon'] ?? null),
                    ImageEntry::make('logo')
                        ->label('Logo')
                        ->state(fn (): ?string => $this->setting['logo']
                            ? self::STORAGE_URL.'/'.$this->setting['logo'].'?v='.time()
                            : null)
                        ->defaultImageUrl(url('assets/img/default.png'))
                        ->height(80)
                        ->circular(false),
                ]),
            ]);
    }

    public function getHeaderActions(): array
    {
        if ($this->setting === null) {
            return [];
        }

        return [
            Action::make('updateSetting')
                ->icon('heroicon-o-pencil-square')
                ->label('Ubah')
                ->color('primary')
                ->visible(fn (): bool => PermissionHelper::hasResource('pengaturan.update'))
                ->modal()
                ->fillForm(fn (): array => $this->setting === null ? [] : [
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
                ->modalHeading('Ubah Pengaturan Sekolah')
                ->modalFooterActions(function (Action $action) {
                    return [
                        $action->getModalSubmitAction()
                            ->label('Simpan')
                            ->color('primary')
                            ->extraAttributes([
                                'class' => 'text-white font-semibold',
                            ]),
                        $action->getModalCancelAction()->label('Batal'),
                    ];
                })
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalSubmitAction()
                ->schema([
                    Section::make('Informasi Sekolah')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('nama_sekolah')
                                        ->label('Nama Sekolah')
                                        ->required(),
                                    TextInput::make('lokasi')
                                        ->label('Lokasi')
                                        ->required(),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    Textarea::make('alamat')
                                        ->label('Alamat')
                                        ->rows(3)
                                        ->required(),
                                    TextInput::make('kode_pos')
                                        ->label('Kode Pos')
                                        ->required(),
                                ]),
                            FileUpload::make('logo')
                                ->label('Logo')
                                ->disk('public')
                                ->visibility('public')
                                ->image()
                                ->moveFiles()
                                ->storeFiles(false),
                        ]),

                    Section::make('Kontak')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->required(),
                                    TextInput::make('telepon')
                                        ->label('Telepon')
                                        ->required(),
                                ]),
                        ]),

                    Section::make('Kepemimpinan')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('kepala_sekolah')
                                        ->label('Kepala Sekolah')
                                        ->required(),
                                    TextInput::make('bendahara')
                                        ->label('Bendahara')
                                        ->required(),
                                ]),
                        ]),
                ])
                ->action(function (array $data, $record): void {
                    if ($this->setting === null) {
                        return;
                    }

                    $photo = null;
                    $originalName = null;
                    $logoData = $data['logo'] ?? null;

                    // Only process if logo is a new uploaded file (not the existing string path)
                    if ($logoData && $logoData instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $photo = file_get_contents($logoData->getRealPath());
                        $originalName = $logoData->getClientOriginalName();
                    }

                    unset($data['logo']);

                    $request = ApiService::client();

                    if (filled($photo)) {
                        $request = $request->attach('logo', $photo, $originalName);
                    }

                    $response = $request
                        ->post('/setting/'.$this->setting['id'], $data);

                    if (! $response->successful()) {
                        Notification::make()
                            ->title('Pengaturan Gagal Diubah')
                            ->danger()
                            ->send();
                    } else {
                        // Re-fetch settings to update displayed data
                        $this->loadSetting();

                        Notification::make()
                            ->title('Pengaturan Berhasil Diubah')
                            ->success()
                            ->send();
                    }
                })
                ->extraAttributes([
                    'class' => 'font-semibold text-white',
                    'id' => 'update',
                ], true),
        ];
    }
}
