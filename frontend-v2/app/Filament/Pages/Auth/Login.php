<?php

namespace App\Filament\Pages\Auth;

use App\Helpers\PermissionHelper;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as PagesLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends PagesLogin
{
    public function mount(): void
    {
        $token = session()->get('data.token');

        if (! is_null($token)) {
            // Superadmin dan admin: redirect ke dashboard
            if (in_array('superadmin', session()->get('data.roles', []))) {
                $this->redirect(filament()->getUrl().'/dashboard-page');

                return;
            }

            // User dengan portal-access: redirect ke portal
            if (PermissionHelper::hasResource('portal-access')) {
                $this->redirect('/'.config('handayani.portal.path', 'portal'));

                return;
            }

            // Default fallback: redirect ke dashboard
            $this->redirect(filament()->getUrl().'/dashboard-page');

            return;
        }

        parent::mount();
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

            $response = Http::post(env('API_URL').'/login', $credentials);

            if ($response->serverError()) {
                Notification::make()
                    ->title('Gagal Login')
                    ->danger()
                    ->body('Tidak dapat terhubung ke server. Silakan coba beberapa saat lagi.')
                    ->send();

                throw ValidationException::withMessages([
                    'data.username' => 'Tidak dapat terhubung ke server. Silakan coba beberapa saat lagi.',
                ]);
            }

            if ($response->successful()) {
                session()->regenerate();

                $responseData = $response->json()['data'];

                session()->put('data.token', $responseData['token']);
                session()->put('data.permissions', $responseData['permissions']);
                session()->put('data.roles', $responseData['roles']);
                session()->put('data.id', $responseData['id']);
                session()->put('data.username', $responseData['username']);
                session()->put('data.must_change_password', $responseData['must_change_password'] ?? false);

                // Login ke Laravel Auth agar Filament mengenali user (untuk user menu)
                Filament::auth()->loginUsingId($responseData['id']);

                // Redirect to change password page if must_change_password is true
                if (! empty($responseData['must_change_password'])) {
                    if (in_array('superadmin', session()->get('data.roles', []))) {
                        $this->redirect(filament()->getUrl().'/change-password');
                    } elseif (PermissionHelper::hasResource('portal-access')) {
                        $this->redirect('/'.config('handayani.portal.path', 'portal').'/change-password');
                    } else {
                        $this->redirect(filament()->getUrl().'/change-password');
                    }

                    return null;
                }

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
                    'data.username' => $message,
                ]);
            }

        } catch (ConnectionException $e) {
            Notification::make()
                ->title('Gagal Login')
                ->danger()
                ->body('Tidak dapat terhubung ke server. Silakan coba beberapa saat lagi.')
                ->send();

            throw ValidationException::withMessages([
                'data.username' => 'Tidak dapat terhubung ke server. Silakan coba beberapa saat lagi.',
            ]);
        } catch (ValidationException $th) {
            throw $th;
        }
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Email / NIS')
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
            'identifier' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
