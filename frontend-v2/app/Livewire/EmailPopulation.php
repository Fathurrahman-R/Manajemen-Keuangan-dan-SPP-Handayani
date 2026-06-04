<?php

namespace App\Livewire;

use App\Services\ApiService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;

class EmailPopulation extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public array $progress = [];

    public function mount(): void
    {
        $this->loadProgress();
    }

    public function loadProgress(): void
    {
        try {
            $progressResponse = ApiService::client()->get('/users/email-population/progress');
            $this->progress = $progressResponse->json('data') ?? [];
        } catch (\Throwable $e) {
            $this->progress = [];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (?string $search, int $page, int $recordsPerPage, ?string $sortColumn = null, ?string $sortDirection = null): LengthAwarePaginator {
                    $params = [
                        'per_page' => $recordsPerPage,
                        'page' => $page,
                    ];

                    if (filled($search)) {
                        $params['search'] = $search;
                    }

                    if (filled($sortColumn)) {
                        $params['sort'] = $sortColumn;
                        $params['direction'] = $sortDirection ?? 'asc';
                    }

                    $response = ApiService::client()
                        ->get('/users/email-population', $params)
                        ->collect();

                    $data = $response['data'] ?? [];

                    return new LengthAwarePaginator(
                        items: $data,
                        total: $response['meta']['total'] ?? count($data),
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                }
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('username')
                    ->label('Username')
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('Belum diisi'),
            ])
            ->recordActions([
                Action::make('editEmail')
                    ->label('Edit Email')
                    ->icon('heroicon-o-pencil')
                    ->iconButton()
                    ->tooltip('Edit Email')
                    ->fillForm(fn (array $record): array => [
                        'email' => $record['email'] ?? '',
                    ])
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->label('Email'),
                    ])
                    ->action(function (array $data, array $record): void {
                        $this->updateEmail($record['id'], $data['email']);
                    })
                    ->modalHeading('Edit Email')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Semua akun sudah memiliki email atau tidak ada akun yang perlu diisi.');
    }

    public function updateEmail(int $userId, string $email): void
    {
        try {
            $response = ApiService::client()->patch("/users/{$userId}/email", [
                'email' => $email,
            ]);

            if ($response->ok()) {
                Notification::make()
                    ->title('Email berhasil disimpan')
                    ->success()
                    ->send();
                $this->loadProgress();
                $this->resetTable();
            } else {
                $errors = $response->json('errors.email') ?? [$response->json('message') ?? 'Gagal menyimpan email.'];
                Notification::make()
                    ->title($errors[0])
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Tidak dapat terhubung ke server.')
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.email-population');
    }
}
