<?php

namespace App\Filament\Pages\Auth;

use App\Services\ApiService;
use BackedEnum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class EditProfile extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms, InteractsWithSchemas {
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
    }

    protected string $view = 'filament.pages.auth.edit-profile';

    protected static ?string $navigationLabel = 'Profil';

    protected static ?string $title = 'Profil Saya';

    protected static ?string $slug = 'profile';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static bool $shouldRegisterNavigation = false;

    // Email form state
    public ?string $email = '';

    public string $current_password_for_email = '';

    public ?string $currentEmail = null;

    public bool $emailVerified = false;

    public string $otp = '';

    public bool $showOtpModal = false;

    // Password form state
    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    // User info
    public ?string $username = null;

    public ?array $roles = [];

    public ?string $branchLocation = null;

    public function mount(): void
    {
        try {
            $response = ApiService::client()->get('/users/current');
            if ($response->ok()) {
                $userData = $response->json('data') ?? [];
                $this->currentEmail = $userData['email'] ?? null;
                $this->emailVerified = ! empty($userData['email_verified_at']);
                $this->email = $this->currentEmail ?? '';
                $this->username = $userData['username'] ?? null;
                $this->roles = $userData['roles'] ?? [];
                $this->branchLocation = $userData['branch']['location'] ?? null;
            }
        } catch (\Throwable $e) {
            // Silent — profile info is non-critical
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
                    ->minLength(8)
                    ->validationMessages([
                        'min' => 'Password baru minimal 8 karakter.',
                    ]),
                TextInput::make('new_password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('new_password')
                    ->validationMessages([
                        'same' => 'Konfirmasi password tidak cocok.',
                    ]),
            ]);
    }

    public function updateEmail(): void
    {
        $this->validate([
            'email' => 'required|email|max:255',
            'current_password_for_email' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'current_password_for_email.required' => 'Password saat ini wajib diisi untuk mengubah email.',
        ]);

        try {
            $response = ApiService::client()->patch('/users/current/email', [
                'email' => $this->email,
                'current_password' => $this->current_password_for_email,
            ]);

            if ($response->ok()) {
                $this->currentEmail = $this->email;
                $this->current_password_for_email = '';
                $this->emailVerified = false;

                Notification::make()
                    ->title('Email berhasil diperbarui')
                    ->success()
                    ->send();
            } else {
                $errors = $response->json('errors', []);
                $message = 'Gagal memperbarui email.';

                if (isset($errors['current_password'])) {
                    $message = is_array($errors['current_password']) ? $errors['current_password'][0] : $errors['current_password'];
                } elseif (isset($errors['email'])) {
                    $message = is_array($errors['email']) ? $errors['email'][0] : $errors['email'];
                }

                Notification::make()
                    ->title('Gagal')
                    ->danger()
                    ->body($message)
                    ->send();
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password_confirmation.same' => 'Konfirmasi password tidak cocok.',
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

                Notification::make()
                    ->title('Password berhasil diubah')
                    ->success()
                    ->send();
            } else {
                $errors = $response->json('errors', []);
                $message = 'Gagal mengubah password.';

                if (isset($errors['current_password'])) {
                    $message = is_array($errors['current_password']) ? $errors['current_password'][0] : $errors['current_password'];
                } elseif (isset($errors['new_password'])) {
                    $message = is_array($errors['new_password']) ? $errors['new_password'][0] : $errors['new_password'];
                }

                Notification::make()
                    ->title('Gagal')
                    ->danger()
                    ->body($message)
                    ->send();
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function sendOtp(): void
    {
        if (! $this->currentEmail) {
            Notification::make()->title('Email belum diatur')->danger()->send();

            return;
        }

        try {
            $response = ApiService::client()->post('/users/send-verification-otp', [
                'email' => $this->currentEmail,
            ]);

            if ($response->successful()) {
                $this->otp = '';
                $this->showOtpModal = true;
                Notification::make()
                    ->title('OTP berhasil dikirim ke email '.$this->currentEmail)
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
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => 'required|string|size:6',
        ]);

        try {
            $response = ApiService::client()->post('/users/verify-email-otp', [
                'email' => $this->currentEmail,
                'otp' => $this->otp,
            ]);

            if ($response->successful()) {
                $this->showOtpModal = false;
                $this->otp = '';
                $this->emailVerified = true;

                Notification::make()
                    ->title('Email berhasil diverifikasi!')
                    ->success()
                    ->send();
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
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Notification::make()
                ->title('Server tidak dapat dihubungi')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function cancelOtp(): void
    {
        $this->showOtpModal = false;
        $this->otp = '';
    }
}
