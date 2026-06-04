<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class ForgotPassword extends SimplePage
{
    protected string $view = 'filament.pages.auth.forgot-password';

    protected static ?string $title = 'Lupa Password';

    public ?array $data = [];

    public bool $sent = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->autofocus(),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            Http::post(env('API_URL') . '/forgot-password', [
                'email' => $data['email'],
            ]);
        } catch (\Throwable $e) {
            // Silent — anti-enumeration
        }

        $this->sent = true;

        Notification::make()
            ->title('Link reset password telah dikirim')
            ->body('Jika email terdaftar, kami telah mengirimkan link reset password.')
            ->success()
            ->send();
    }
}
