<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use App\Filament\Pages\Auth\LoginResponse;

// use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Facades\Filament;
use Filament\Auth\Pages\Login as PagesLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class Login extends PagesLogin
{

    public function __construct()
    {
        $token = session()->get('data');

        if (!is_null($token) && !is_null($token['token'])) {
            return redirect()->intended(filament()->getUrl() . '/data-master-siswa');
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getUsernameFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        try {
            $data = $this->form->getState();
    
            $credentials = $this->getCredentialsFromFormData($data);
    
            $response = Http::post(env('API_URL') . '/users/login', $credentials);
    
            if ($response->successful()) {
                session()->regenerate();
                session($response->json());
                
                Session::put('data', $response->json()['data']);

                Filament::auth()->loginUsingId($response->json()['data']['id']);

                return app(LoginResponse::class);
            } else {
                $errorKeys = array_keys($response->json()['errors']);
                
                $message = $response->json()['errors'][$errorKeys[0]][0];
                
                Notification::make()
                    ->title('Gagal Login')
                    ->danger()
                    ->body($message)
                    ->send();

                throw ValidationException::withMessages([
                    'email' => $message
                ]);
            }
            
        } catch (ValidationException $th) {
            throw $th;
        }
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('Username'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::auth/pages/login.form.remember.label'));
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
