<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;

class NotificationSettings extends Component implements HasForms
{
    use HandlesApiErrors;
    use InteractsWithForms;

    public ?array $data = [];

    public bool $loaded = false;

    public function mount(): void
    {
        try {
            $response = ApiService::client()->get('/notification-settings');

            if ($response->ok()) {
                $setting = $response->json('data');
                $this->form->fill([
                    'tagihan_baru_enabled' => $setting['tagihan_baru_enabled'] ?? true,
                    'reminder_enabled' => $setting['reminder_enabled'] ?? true,
                    'kwitansi_enabled' => $setting['kwitansi_enabled'] ?? true,
                    'overdue_enabled' => $setting['overdue_enabled'] ?? true,
                    'reminder_days_before' => array_map('strval', $setting['reminder_days_before'] ?? [7, 3, 1]),
                    'overdue_interval_days' => $setting['overdue_interval_days'] ?? 7,
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
                Section::make('Notifikasi Tagihan Baru')
                    ->description('Kirim email otomatis ke wali siswa ketika tagihan baru dibuat.')
                    ->schema([
                        Checkbox::make('tagihan_baru_enabled')
                            ->label('Aktifkan notifikasi tagihan baru'),
                    ])
                    ->collapsible(),

                Section::make('Notifikasi Kwitansi Pembayaran')
                    ->description('Kirim email kwitansi otomatis ke wali siswa setelah pembayaran tercatat.')
                    ->schema([
                        Checkbox::make('kwitansi_enabled')
                            ->label('Aktifkan notifikasi kwitansi'),
                    ])
                    ->collapsible(),

                Section::make('Pengingat Jatuh Tempo')
                    ->description('Kirim email pengingat sebelum tagihan jatuh tempo.')
                    ->schema([
                        Checkbox::make('reminder_enabled')
                            ->label('Aktifkan pengingat jatuh tempo'),
                        TagsInput::make('reminder_days_before')
                            ->label('Kirim pengingat H-berapa sebelum jatuh tempo')
                            ->helperText('Masukkan angka hari, contoh: 7, 3, 1 (untuk H-7, H-3, H-1)')
                            ->placeholder('Tambahkan angka hari...')
                            ->separator(','),
                    ])
                    ->collapsible(),

                Section::make('Notifikasi Keterlambatan')
                    ->description('Kirim email pengingat untuk tagihan yang sudah melewati jatuh tempo.')
                    ->schema([
                        Checkbox::make('overdue_enabled')
                            ->label('Aktifkan notifikasi keterlambatan'),
                        TextInput::make('overdue_interval_days')
                            ->label('Interval pengiriman (hari)')
                            ->helperText('Kirim ulang notifikasi setiap berapa hari sekali setelah jatuh tempo.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->suffix('hari'),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Convert reminder_days_before tags to integer array
        $reminderDays = [];
        $rawReminderDays = $state['reminder_days_before'] ?? [];
        
        if (is_string($rawReminderDays)) {
            $decoded = json_decode($rawReminderDays, true);
            $rawReminderDays = is_array($decoded) ? $decoded : explode(',', $rawReminderDays);
        }

        if (is_array($rawReminderDays) && !empty($rawReminderDays)) {
            foreach ($rawReminderDays as $v) {
                // Strip all non-numeric characters (e.g. if user types 'H-7' or '7 hari')
                $val = intval(preg_replace('/[^0-9]/', '', (string) $v));
                if ($val > 0 && $val <= 90) {
                    $reminderDays[] = $val;
                }
            }
        }

        // Default fallback if somehow empty but reminder is enabled, or to pass min:1
        if (empty($reminderDays)) {
            $reminderDays = [7, 3, 1];
        } else {
            $reminderDays = array_unique($reminderDays);
            rsort($reminderDays);
        }

        $payload = [
            'tagihan_baru_enabled' => (bool) ($state['tagihan_baru_enabled'] ?? false),
            'reminder_enabled' => (bool) ($state['reminder_enabled'] ?? false),
            'kwitansi_enabled' => (bool) ($state['kwitansi_enabled'] ?? false),
            'overdue_enabled' => (bool) ($state['overdue_enabled'] ?? false),
            'reminder_days_before' => $reminderDays,
            'overdue_interval_days' => (int) ($state['overdue_interval_days'] ?? 7),
        ];

        try {
            $response = ApiService::client()->put('/notification-settings', $payload);

            if ($response->ok()) {
                Notification::make()
                    ->title('Pengaturan notifikasi berhasil disimpan.')
                    ->success()
                    ->send();
            } else {
                $this->handleApiError($response);
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        }
    }

    public function render()
    {
        return view('livewire.notification-settings');
    }
}
