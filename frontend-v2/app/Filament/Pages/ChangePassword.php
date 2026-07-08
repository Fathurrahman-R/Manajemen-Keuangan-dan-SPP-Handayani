<?php

namespace App\Filament\Pages;

use App\Services\ApiService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.change-password';

    protected static ?string $title = 'Ubah Password';

    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public bool $isFirstTimeUser = false;

    public bool $isEmailVerified = false;

    public function mount(): void
    {
        // Only allow access if must_change_password is true
        if (!session()->get('data.must_change_password', false)) {
            $this->redirectToMainPage();
        }

        $this->isFirstTimeUser = true;
        $this->isEmailVerified = false;

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengaturan Email')
                    ->description('Anda wajib mengatur dan memverifikasi email terlebih dahulu sebelum mengubah password.')
                    ->visible(fn () => $this->isFirstTimeUser && !$this->isEmailVerified)
                    ->schema([
                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->helperText('Masukkan email Anda. Jika tidak menerima OTP, Anda bisa mengubah email dan mengirim ulang.'),
                        \Filament\Schemas\Components\Actions::make([
                            \Filament\Actions\Action::make('sendOtp')
                                ->label('Kirim Kode OTP')
                                ->action('sendOtpAction')
                                ->button(),
                        ]),
                        TextInput::make('otp')
                            ->label('Kode OTP Verifikasi')
                            ->required()
                            ->length(6)
                            ->extraInputAttributes(['inputmode' => 'numeric']),
                    ]),
                    
                Section::make('Ubah Password')
                    ->description('Anda harus mengubah password sebelum melanjutkan. Silakan masukkan password baru Anda.')
                    ->visible(fn () => $this->isEmailVerified)
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->required(),
                        TextInput::make('new_password')
                            ->label('Password Baru')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        TextInput::make('new_password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label($this->isEmailVerified ? 'Ubah Password' : 'Verifikasi Email')
                ->submit('submit')
                ->keyBindings(['mod+s']),
        ];
    }

    public function sendOtpAction(): void
    {
        $state = $this->form->getRawState();
        if (empty($state['email'])) {
            Notification::make()
                ->title('Email harus diisi')
                ->danger()
                ->send();
            return;
        }

        $response = ApiService::client()->post('/users/send-verification-otp', [
            'email' => $state['email']
        ]);

        if ($response->successful()) {
            Notification::make()
                ->title('OTP berhasil dikirim ke email Anda.')
                ->body('Jika tidak menerima OTP, Anda bisa mengubah alamat email dan klik "Kirim Kode OTP" lagi.')
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
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        if ($this->isFirstTimeUser && !$this->isEmailVerified) {
            $response = ApiService::client()->post('/users/verify-email-otp', [
                'email' => $state['email'] ?? null,
                'otp' => $state['otp'] ?? null,
            ]);

            if ($response->successful()) {
                $this->isEmailVerified = true;
                
                // Refresh form schema and layout
                $this->form->fill();
                
                Notification::make()
                    ->title('Email berhasil diverifikasi!')
                    ->body('Sekarang silakan ubah password Anda.')
                    ->success()
                    ->send();
            } else {
                $errors = $response->json('errors', []);
                $message = $response->json('message', 'Gagal verifikasi email.');
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
            return;
        }

        $payload = [
            'current_password' => $state['current_password'],
            'new_password' => $state['new_password'],
            'new_password_confirmation' => $state['new_password_confirmation'],
        ];

        $response = ApiService::client()->post('/users/change-password', $payload);

        if ($response->successful()) {
            session()->put('data.must_change_password', false);

            Notification::make()
                ->title('Password berhasil diubah')
                ->success()
                ->send();

            // Logout dari API (hapus token Sanctum) lalu clear session
            try {
                ApiService::client()->delete('/logout');
            } catch (\Exception $e) {
                // Tetap lanjut meskipun API call gagal
            }

            \Filament\Facades\Filament::auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            $this->redirect('/login');
        } else {
            $errors = $response->json('errors', []);
            $message = 'Gagal mengubah password.';

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
    }

    protected function redirectToMainPage(): void
    {
        $roles = session()->get('data.roles', []);

        if (in_array('siswa', $roles)) {
            $this->redirect('/' . config('handayani.portal.path', 'portal'));
        } else {
            $this->redirect(filament()->getUrl() . '/dashboard-page');
        }
    }
}
