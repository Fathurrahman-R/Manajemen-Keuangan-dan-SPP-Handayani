<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\MidtransApi;
use App\Services\MidtransApiException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Livewire\Component;

class TransaksiMidtransDetail extends Component implements HasActions, HasSchemas
{
    use HandlesApiErrors;
    use InteractsWithActions, InteractsWithSchemas;

    public string $orderId;

    public ?array $transaction = null;

    public ?array $logs = null;

    public bool $loadError = false;

    /**
     * Sensitive fields that should be masked in display.
     */
    protected array $maskedFields = ['signature_key', 'server_key'];

    public function mount(string $orderId): void
    {
        $this->orderId = $orderId;
        $this->loadTransaction();
        $this->loadLogs();
    }

    protected function loadTransaction(): void
    {
        try {
            $response = MidtransApi::adminShow($this->orderId);
            $this->transaction = $this->maskSensitiveFields($response['data'] ?? $response);
            $this->loadError = false;
        } catch (MidtransApiException $e) {
            $this->loadError = true;
            Notification::make()
                ->title('Gagal memuat transaksi')
                ->body($e->getUserMessage())
                ->danger()
                ->send();
        } catch (ConnectionException $e) {
            $this->loadError = true;
            $this->notifyConnectionError();
        } catch (\Throwable $e) {
            $this->loadError = true;
            $this->notifyUnexpectedError();
        }
    }

    protected function loadLogs(): void
    {
        try {
            $response = MidtransApi::adminLogs($this->orderId);
            $this->logs = $response['data'] ?? $response;
        } catch (\Throwable $e) {
            $this->logs = [];
        }
    }

    /**
     * Mask sensitive fields in the transaction data.
     */
    protected function maskSensitiveFields(array $data): array
    {
        foreach ($this->maskedFields as $field) {
            if (isset($data[$field]) && ! empty($data[$field])) {
                $data[$field] = '***';
            }
        }

        // Also mask in nested raw response if present
        if (isset($data['last_raw_response']) && is_array($data['last_raw_response'])) {
            foreach ($this->maskedFields as $field) {
                if (isset($data['last_raw_response'][$field]) && ! empty($data['last_raw_response'][$field])) {
                    $data['last_raw_response'][$field] = '***';
                }
            }
        }

        return $data;
    }

    /**
     * Mask payload snippet for audit log display.
     */
    protected function maskPayloadSnippet(?string $payload): string
    {
        if (empty($payload)) {
            return '-';
        }

        // Truncate to snippet length
        $snippet = mb_strlen($payload) > 120 ? mb_substr($payload, 0, 120).'...' : $payload;

        // Mask any remaining sensitive values in snippet
        $snippet = preg_replace('/"(signature_key|server_key)"\s*:\s*"[^"]*"/', '"$1": "***"', $snippet);

        return $snippet;
    }

    public function syncAction(): Action
    {
        return Action::make('sync')
            ->label('Sinkronisasi Status')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Sinkronisasi Status Transaksi')
            ->modalDescription('Apakah Anda yakin ingin melakukan sinkronisasi status transaksi ini dengan Midtrans?')
            ->modalSubmitActionLabel('Ya, Sinkronisasi')
            ->visible(fn (): bool => ($this->transaction['status'] ?? '') === 'pending' || PermissionHelper::hasResource('midtrans.sync'))
            ->action(function (): void {
                try {
                    MidtransApi::sync($this->orderId);

                    Notification::make()
                        ->title('Sinkronisasi Berhasil')
                        ->body('Status transaksi telah diperbarui.')
                        ->success()
                        ->send();

                    // Reload data
                    $this->loadTransaction();
                    $this->loadLogs();
                } catch (MidtransApiException $e) {
                    Notification::make()
                        ->title('Sinkronisasi Gagal')
                        ->body($e->getUserMessage())
                        ->danger()
                        ->send();
                } catch (ConnectionException $e) {
                    $this->notifyConnectionError();
                } catch (\Throwable $e) {
                    $this->notifyUnexpectedError();
                }
            });
    }

    public function getStatusColor(): string
    {
        return match ($this->transaction['status'] ?? '') {
            'settlement', 'capture' => 'success',
            'pending' => 'warning',
            'deny', 'cancel', 'expire', 'failure' => 'danger',
            'refund', 'partial_refund' => 'info',
            default => 'gray',
        };
    }

    public function getFormattedLogs(): array
    {
        if (empty($this->logs)) {
            return [];
        }

        return collect($this->logs)->map(fn (array $log) => [
            'direction' => match ($log['direction'] ?? '') {
                'outbound_charge' => 'Outbound (Charge)',
                'outbound_status' => 'Outbound (Status)',
                'inbound_notification' => 'Inbound (Notifikasi)',
                default => $log['direction'] ?? '-',
            },
            'http_status' => $log['http_status'] ?? '-',
            'payload_snippet' => $this->maskPayloadSnippet($log['raw_payload'] ?? null),
            'created_at' => isset($log['created_at'])
                ? \Carbon\Carbon::parse($log['created_at'])->format('d/m/Y H:i:s')
                : '-',
        ])->toArray();
    }

    public function render(): View
    {
        return view('livewire.transaksi-midtrans-detail');
    }
}
