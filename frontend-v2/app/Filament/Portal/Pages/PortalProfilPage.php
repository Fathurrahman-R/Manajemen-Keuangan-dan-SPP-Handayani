<?php

namespace App\Filament\Portal\Pages;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\ApiService;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\ConnectionException;

class PortalProfilPage extends Page implements HasForms
{
    use HandlesApiErrors;
    use InteractsWithForms, InteractsWithSchemas {
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
    }

    protected string $view = 'filament.portal.pages.profil';

    protected static ?string $navigationLabel = 'Profil';

    protected static ?string $title = 'Profil Saya';

    protected static ?string $slug = 'profil';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // User info
    public ?string $username = null;

    public ?string $currentEmail = null;

    public ?array $roles = [];

    public ?string $branchLocation = null;

    public ?string $ayahEmail = null;

    public ?string $ibuEmail = null;

    public ?string $waliEmail = null;

    public bool $emailVerified = false;

    public bool $ayahEmailVerified = false;

    public bool $ibuEmailVerified = false;

    public bool $waliEmailVerified = false;

    // Parent OTP modal state
    public ?string $parentOtpType = null;

    public string $parentOtp = '';

    public bool $showParentOtpModal = false;

    // Email form
    public ?string $email = '';

    public string $current_password_for_email = '';

    // Password form
    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    // Notification form
    public bool $notif_tagihan_baru = false;

    public bool $notif_reminder = false;

    public bool $notif_kwitansi = false;

    public bool $notif_overdue = false;

    // Siswa detail (read-only)
    public ?array $siswaDetail = [];

    public ?string $siswaJenjang = null;

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (! PermissionHelper::hasResource('portal-access')) {
            abort(403);
        }

        try {
            $response = ApiService::client()->get('/users/current');

            if (! $response->ok()) {
                $this->handleApiError($response);

                return;
            }

            $userData = $response->json('data') ?? [];
            $this->currentEmail = $userData['email'] ?? null;
            $this->email = $this->currentEmail ?? '';
            $this->username = $userData['username'] ?? null;
            $this->roles = $userData['roles'] ?? [];
            $this->branchLocation = $userData['branch']['location'] ?? null;
            $this->emailVerified = ! empty($userData['email_verified_at']);

            if (isset($userData['siswa'])) {
                $this->ayahEmail = $userData['siswa']['ayah']['email'] ?? null;
                $this->ibuEmail = $userData['siswa']['ibu']['email'] ?? null;
                $this->waliEmail = $userData['siswa']['wali']['email'] ?? null;
                $this->ayahEmailVerified = ! empty($userData['siswa']['ayah']['email_verified_at']);
                $this->ibuEmailVerified = ! empty($userData['siswa']['ibu']['email_verified_at']);
                $this->waliEmailVerified = ! empty($userData['siswa']['wali']['email_verified_at']);
            }

            // Fetch notification preferences if email is set
            if ($this->currentEmail) {
                $notifResponse = ApiService::client()->get('/users/current/notification-preferences');
                if ($notifResponse->ok()) {
                    $notifData = $notifResponse->json('data') ?? [];
                    $this->notif_tagihan_baru = $notifData['tagihan_baru'] ?? false;
                    $this->notif_reminder = $notifData['reminder'] ?? false;
                    $this->notif_kwitansi = $notifData['kwitansi'] ?? false;
                    $this->notif_overdue = $notifData['overdue'] ?? false;
                }
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }

        // Fetch siswa detail (read-only)
        try {
            $detailResponse = ApiService::client()->get('/users/current/siswa-detail');
            if ($detailResponse->ok()) {
                $this->siswaDetail = $detailResponse->json('data') ?? [];
                $this->siswaJenjang = $this->siswaDetail['siswa']['jenjang'] ?? null;
            }
        } catch (ConnectionException $e) {
            // Silently skip — siswa detail is supplementary
        }
    }

    public function emailFormSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email Baru')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('email@contoh.com'),
                TextInput::make('current_password_for_email')
                    ->label('Password Saat Ini')
                    ->password()
                    ->revealable()
                    ->required()
                    ->placeholder('Masukkan password untuk konfirmasi'),
            ]);
    }

    public function passwordFormSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Password Saat Ini')
                    ->password()
                    ->revealable()
                    ->required(),
                TextInput::make('new_password')
                    ->label('Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8),
                TextInput::make('new_password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('new_password'),
            ]);
    }

    public function notificationFormSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Toggle::make('notif_tagihan_baru')
                    ->label('Tagihan Baru')
                    ->helperText('Notifikasi saat tagihan baru diterbitkan'),
                \Filament\Forms\Components\Toggle::make('notif_reminder')
                    ->label('Reminder Jatuh Tempo')
                    ->helperText('Pengingat beberapa hari sebelum tanggal jatuh tempo'),
                \Filament\Forms\Components\Toggle::make('notif_kwitansi')
                    ->label('Kwitansi Pembayaran')
                    ->helperText('Bukti pembayaran setiap kali transaksi berhasil'),
                \Filament\Forms\Components\Toggle::make('notif_overdue')
                    ->label('Tagihan Menunggak')
                    ->helperText('Pemberitahuan ketika tagihan melewati tanggal jatuh tempo'),
            ]);
    }

    public function updateEmail(): void
    {
        $this->validate([
            'email' => 'required|email|max:255',
            'current_password_for_email' => 'required|string',
        ]);

        try {
            $response = ApiService::client()->patch('/users/current/email', [
                'email' => $this->email,
                'current_password' => $this->current_password_for_email,
            ]);

            if ($response->ok()) {
                $this->currentEmail = $this->email;
                $this->current_password_for_email = '';

                // Fetch default preferences on email set if it was previously unset
                $notifResponse = ApiService::client()->get('/users/current/notification-preferences');
                if ($notifResponse->ok()) {
                    $notifData = $notifResponse->json('data') ?? [];
                    $this->notif_tagihan_baru = $notifData['tagihan_baru'] ?? false;
                    $this->notif_reminder = $notifData['reminder'] ?? false;
                    $this->notif_kwitansi = $notifData['kwitansi'] ?? false;
                    $this->notif_overdue = $notifData['overdue'] ?? false;
                }

                Notification::make()->title('Email berhasil diperbarui')->success()->send();
            } else {
                $this->handleApiError($response);
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ]);

        try {
            $response = ApiService::client()->post('/users/change-password', [
                'current_password' => $this->current_password,
                'new_password' => $this->new_password,
                'new_password_confirmation' => $this->new_password_confirmation,
            ]);

            if ($response->ok()) {
                $this->current_password = '';
                $this->new_password = '';
                $this->new_password_confirmation = '';
                Notification::make()->title('Password berhasil diubah')->success()->send();
            } else {
                $this->handleApiError($response);
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }
    }

    public function updateNotificationPreferences(): void
    {
        if (! $this->currentEmail) {
            Notification::make()->title('Email belum diatur')->danger()->send();

            return;
        }

        try {
            $response = ApiService::client()->put('/users/current/notification-preferences', [
                'tagihan_baru' => $this->notif_tagihan_baru,
                'reminder' => $this->notif_reminder,
                'kwitansi' => $this->notif_kwitansi,
                'overdue' => $this->notif_overdue,
            ]);

            if ($response->ok()) {
                Notification::make()->title('Preferensi notifikasi berhasil disimpan')->success()->send();
            } else {
                $this->handleApiError($response);
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }
    }

    public function sendParentOtp(string $type): void
    {
        try {
            $response = ApiService::client()->post('/users/send-wali-otp', [
                'type' => $type,
            ]);

            if ($response->successful()) {
                $this->parentOtpType = $type;
                $this->parentOtp = '';
                $this->showParentOtpModal = true;

                $label = match ($type) {
                    'ayah' => 'Ayah',
                    'ibu' => 'Ibu',
                    'wali' => 'Wali',
                };
                Notification::make()
                    ->title('OTP berhasil dikirim ke email '.$label)
                    ->success()
                    ->send();
            } else {
                $errors = $response->json('errors', []);
                $message = $response->json('message', 'Gagal mengirim OTP.');
                if (count($errors) > 0) {
                    $firstError = reset($errors);
                    $message = is_array($firstError) ? $firstError[0] : (string) $firstError;
                }
                Notification::make()
                    ->title('Gagal')
                    ->danger()
                    ->body($message)
                    ->send();
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }
    }

    public function verifyParentOtp(): void
    {
        $this->validate([
            'parentOtp' => 'required|string|size:6',
        ]);

        try {
            $response = ApiService::client()->post('/users/verify-wali-otp', [
                'type' => $this->parentOtpType,
                'otp' => $this->parentOtp,
            ]);

            if ($response->successful()) {
                $this->showParentOtpModal = false;
                $this->parentOtp = '';

                // Update local verified state
                match ($this->parentOtpType) {
                    'ayah' => $this->ayahEmailVerified = true,
                    'ibu' => $this->ibuEmailVerified = true,
                    'wali' => $this->waliEmailVerified = true,
                };

                $label = match ($this->parentOtpType) {
                    'ayah' => 'Ayah',
                    'ibu' => 'Ibu',
                    'wali' => 'Wali',
                };
                Notification::make()
                    ->title('Email '.$label.' berhasil diverifikasi!')
                    ->success()
                    ->send();

                $this->parentOtpType = null;
            } else {
                $errors = $response->json('errors', []);
                $message = $response->json('message', 'Gagal verifikasi OTP.');
                if (count($errors) > 0) {
                    $firstError = reset($errors);
                    $message = is_array($firstError) ? $firstError[0] : (string) $firstError;
                }
                Notification::make()
                    ->title('Gagal')
                    ->danger()
                    ->body($message)
                    ->send();
            }
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }
    }

    public function cancelParentOtp(): void
    {
        $this->showParentOtpModal = false;
        $this->parentOtp = '';
        $this->parentOtpType = null;
    }
}
