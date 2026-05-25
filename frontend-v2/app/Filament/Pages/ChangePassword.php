<?php

namespace App\Filament\Pages;

use App\Services\ApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ChangePassword extends Page
{
    protected string $view = 'filament.pages.change-password';

    protected static ?string $title = 'Ubah Password';

    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        // Only allow access if must_change_password is true
        if (!session()->get('data.must_change_password', false)) {
            $this->redirectToMainPage();
        }
    }

    public function submit(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ]);

        $response = ApiService::client()->post('/users/change-password', [
            'current_password' => $this->current_password,
            'new_password' => $this->new_password,
            'new_password_confirmation' => $this->new_password_confirmation,
        ]);

        if ($response->successful()) {
            session()->put('data.must_change_password', false);

            Notification::make()
                ->title('Password berhasil diubah')
                ->success()
                ->send();

            $this->redirectToMainPage();
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
            $this->redirect(filament()->getUrl() . '/tagihan-siswa');
        } else {
            $this->redirect(filament()->getUrl() . '/data-master-siswa');
        }
    }
}
