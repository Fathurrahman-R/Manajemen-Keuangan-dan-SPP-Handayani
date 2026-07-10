<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Alignment;
use App\Services\ApiService;
use Filament\Notifications\Notification;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Client\ConnectionException;

class Settings extends Page
{
    use HandlesApiErrors;

    protected string $view = 'filament.pages.settings';

    protected static ?string $title = 'Pengaturan';

    protected static ?string $slug = 'setting';

    public ?array $setting;

    public function mount()
    {
        abort_if(!PermissionHelper::hasResource('app-setting'), 403);

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
                ->visible(fn(): bool => PermissionHelper::hasResource('app-setting.update'))
                ->modal()
                ->fillForm(fn(): array => $this->setting === null ? [] : [
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
                            ->color('primary')
                            ->extraAttributes([
                                'class' => 'text-white font-semibold'
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
                        ->post('/setting/' . $this->setting['id'], $data);

                    if (!$response->successful()) {
                        Notification::make()
                            ->title('Pengaturan Gagal Diubah')
                            ->danger()
                            ->send();
                    } else {
                        // Re-fetch settings to update displayed data
                        try {
                            $refreshResponse = ApiService::client()->get('/setting');
                            if ($refreshResponse->ok()) {
                                $this->setting = $refreshResponse->json('data');
                            }
                        } catch (\Throwable $e) {
                            // Silently ignore refresh failure — data was saved successfully
                        }

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
