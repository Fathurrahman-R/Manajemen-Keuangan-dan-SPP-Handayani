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

    public function mount(): void
    {
        // Only allow access if must_change_password is true
        if (!session()->get('data.must_change_password', false)) {
            $this->redirectToMainPage();
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ubah Password')
                    ->description('Anda harus mengubah password sebelum melanjutkan. Silakan masukkan password baru Anda.')
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

    public function submit(): void
    {
        $state = $this->form->getState();

        $response = ApiService::client()->post('/users/change-password', [
            'current_password' => $state['current_password'],
            'new_password' => $state['new_password'],
            'new_password_confirmation' => $state['new_password_confirmation'],
        ]);

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

            $this->redirect(filament()->getUrl() . '/login');
        } else {
            $errors = $response->json('errors', []);
            $message = 'Gagal mengubah password.';

            if (isset($errors['current_password'])) {
                $message = is_array($errors['current_password'])
                    ? $errors['current_password'][0]
                    : $errors['current_password'];
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
            $this->redirect(filament()->getUrl() . '/data-master-siswa');
        }
    }
}
