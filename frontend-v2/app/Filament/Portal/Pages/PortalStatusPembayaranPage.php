<?php

namespace App\Filament\Portal\Pages;

use App\Services\MidtransApi;
use App\Services\MidtransApiException;
use BackedEnum;
use Filament\Pages\Page;

class PortalStatusPembayaranPage extends Page
{
    protected string $view = 'filament.portal.pages.status-pembayaran';

    protected static ?string $navigationLabel = 'Status Pembayaran';

    protected static ?string $title = 'Status Pembayaran';

    protected static ?string $slug = 'status-pembayaran';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // Page properties
    public string $order_id = '';
    public array $transaction = [];
    public string $status = 'pending';
    public int $pollCount = 0;
    public bool $isPolling = true;
    public bool $loadError = false;

    protected ?string $heading = 'Status Pembayaran';

    public function mount(): void
    {
        $roles = session()->get('data.roles', []);
        if (!in_array('siswa', $roles) && !in_array('wali', $roles)) {
            abort(403);
        }

        $this->order_id = request()->query('order_id', '');

        if (empty($this->order_id)) {
            abort(404);
        }

        $this->fetchStatus();
    }

    /**
     * Fetch transaction status from backend API.
     */
    public function fetchStatus(): void
    {
        try {
            $result = MidtransApi::show($this->order_id);
            $this->transaction = $result['data'] ?? $result;
            $this->status = $this->transaction['status'] ?? 'pending';
            $this->loadError = false;

            // Check if status is terminal — stop polling
            if ($this->isTerminalStatus($this->status)) {
                $this->isPolling = false;
            }
        } catch (MidtransApiException $e) {
            $this->loadError = true;
            $this->isPolling = false;
        } catch (\Throwable $e) {
            $this->loadError = true;
            $this->isPolling = false;
        }
    }

    /**
     * Called by wire:poll to refresh the status.
     */
    public function pollStatus(): void
    {
        if (!$this->isPolling) {
            return;
        }

        $this->pollCount++;

        // Stop at 24 polls (120 seconds at 5s interval)
        if ($this->pollCount >= 24) {
            $this->isPolling = false;
            return;
        }

        $this->fetchStatus();
    }

    /**
     * Check if a status is terminal.
     */
    public function isTerminalStatus(string $status): bool
    {
        return in_array($status, ['settlement', 'capture', 'deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund']);
    }

    /**
     * Map internal status to a display-friendly status label.
     */
    public function getDisplayStatus(): string
    {
        return match ($this->status) {
            'pending' => __('midtrans.status_pending', [], 'id'),
            'settlement', 'capture' => __('midtrans.status_success', [], 'id'),
            'deny', 'failure' => __('midtrans.status_failed', [], 'id'),
            'expire' => __('midtrans.status_expired', [], 'id'),
            'cancel' => __('midtrans.status_cancelled', [], 'id'),
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the CSS color class for the status badge.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'settlement', 'capture' => 'green',
            'deny', 'failure' => 'red',
            'expire' => 'gray',
            'cancel' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Whether the payment was successful.
     */
    public function isSuccess(): bool
    {
        return in_array($this->status, ['settlement', 'capture']);
    }
}
