<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class ResetPassword extends SimplePage
{
    protected string $view = 'filament.pages.auth.reset-password';

    protected static ?string $title = 'Reset Password';

    public ?array $data = [];

    public ?string $token = null;

    public bool $tokenValid = false;

    public bool $success = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->token = request()->query('token');

        if (! $this->token) {
            $this->errorMessage = 'Token tidak ditemukan.';

            return;
        }

        // Validate token
        try {
            $response = Http::get(env('API_URL').'/reset-password/'.$this->token);

            if ($response->ok() && $response->json('valid')) {
                $this->tokenValid = true;
            } else {
                $this->errorMessage = $response->json('message') ?? 'Token tidak valid atau sudah kadaluarsa.';
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Tidak dapat terhubung ke server.';
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8),
                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password'),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            $response = Http::post(env('API_URL').'/reset-password', [
                'token' => $this->token,
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ]);

            if ($response->ok()) {
                $this->success = true;
                Notification::make()
                    ->title('Password Berhasil Direset')
                    ->success()
                    ->send();
            } else {
                $this->errorMessage = $response->json('message') ?? 'Gagal mereset password.';
                Notification::make()
                    ->title('Gagal')
                    ->body($this->errorMessage)
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body('Tidak dapat terhubung ke server.')
                ->danger()
                ->send();
        }
    }
}
