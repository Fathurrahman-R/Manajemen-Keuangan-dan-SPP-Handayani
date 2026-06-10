<?php

namespace App\Filament\Portal\Pages;

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

class PortalProfilPage extends Page implements HasForms, HasSchemas
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

    // Email form
    public ?string $email = '';
    public string $current_password_for_email = '';

    // Password form
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (!in_array('siswa', $roles) && !in_array('wali', $roles)) {
            abort(403);
        }

        try {
            $response = ApiService::client()->get('/users/current');

            if (!$response->ok()) {
                $this->handleApiError($response);
                return;
            }

            $userData = $response->json('data') ?? [];
            $this->currentEmail = $userData['email'] ?? null;
            $this->email = $this->currentEmail ?? '';
            $this->username = $userData['username'] ?? null;
            $this->roles = $userData['roles'] ?? [];
            $this->branchLocation = $userData['branch']['location'] ?? null;
        } catch (ConnectionException $e) {
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
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
}
