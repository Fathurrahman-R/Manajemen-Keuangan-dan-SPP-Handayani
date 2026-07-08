<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;

class BranchApprovalSettings extends Component implements HasForms
{
    use InteractsWithForms;
    use HandlesApiErrors;

    public ?array $data = [];

    public bool $loaded = false;

    public function mount(): void
    {
        try {
            $response = ApiService::client()->get('/branch-approval-settings');

            if ($response->ok()) {
                $setting = $response->json('data');
                $this->form->fill([
                    'auto_approval_enabled' => (bool) ($setting['auto_approval_enabled'] ?? false),
                    'auto_approval_threshold' => (string) ($setting['auto_approval_threshold'] ?? 0),
                ]);
                $this->loaded = true;
            } else {
                $this->handleApiError($response);
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Approval Otomatis Pengeluaran')
                    ->description('Konfigurasi approval otomatis untuk pengeluaran di cabang ini.')
                    ->schema([
                        Toggle::make('auto_approval_enabled')
                            ->label('Aktifkan approval otomatis')
                            ->helperText('Jika diaktifkan, pengeluaran di bawah threshold akan otomatis disetujui.'),
                        TextInput::make('auto_approval_threshold')
                            ->label('Threshold (Rp)')
                            ->helperText('Nominal maksimal pengeluaran yang bisa di-approve otomatis.')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $response = ApiService::client()->put('/branch-approval-settings', [
                'auto_approval_enabled' => (bool) $data['auto_approval_enabled'],
                'auto_approval_threshold' => (float) ($data['auto_approval_threshold'] ?? 0),
            ]);

            if ($response->ok()) {
                Notification::make()
                    ->title('Pengaturan approval berhasil disimpan')
                    ->success()
                    ->send();
            } else {
                $this->handleApiError($response);
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat menyimpan pengaturan.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-approval-settings');
    }
}
