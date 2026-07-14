<?php

namespace App\Livewire;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use App\Services\MidtransApi;
use App\Services\MidtransApiException;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class TagihanSiswa extends Component implements HasActions, HasSchemas
{
    use \App\Livewire\Concerns\HandlesApiErrors;
    use InteractsWithActions;
    use InteractsWithSchemas;

    public array $tagihanData = [];

    public array $siblings = [];

    public ?int $selectedSiswaId = null;

    public ?string $selectedSiswaName = null;

    public ?int $ownerSiswaId = null;

    public ?string $ownerSiswaName = null;

    /** Kode tagihan yang dipilih siswa untuk bayar batch online. */
    public array $selectedKodeTagihan = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $params = [];

        if ($this->selectedSiswaId) {
            $params['siswa_id'] = $this->selectedSiswaId;
        }

        try {
            $response = ApiService::client()->get('/tagihan/siswa', $params);

            if ($response->ok()) {
                $json = $response->json();
                $data = $json['data'] ?? [];
                $this->tagihanData = $data['tagihan'] ?? [];
                $this->siblings = $data['siblings'] ?? [];
                $this->selectedSiswaId = $data['selected_siswa_id'] ?? null;
                $this->selectedSiswaName = $data['selected_siswa_nama'] ?? null;

                // Store owner info on first load (when no siswa_id param was sent)
                if ($this->ownerSiswaId === null) {
                    $this->ownerSiswaId = $this->selectedSiswaId;
                    $this->ownerSiswaName = $this->selectedSiswaName;
                }
            } else {
                $this->handleApiError($response);
                $this->tagihanData = [];
                $this->siblings = [];
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->notifyConnectionError();
            $this->tagihanData = [];
            $this->siblings = [];
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
            $this->tagihanData = [];
            $this->siblings = [];
        }
    }

    public function updatedSelectedSiswaId(): void
    {
        // Reset selection when switching sibling.
        $this->selectedKodeTagihan = [];
        $this->loadData();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getBatchEligibleProperty(): array
    {
        $midtransOn = $this->isMidtransEnabled();

        return collect($this->tagihanData)
            ->filter(function (array $t) use ($midtransOn): bool {
                $sisa = (int) (($t['jenis_tagihan']['jumlah'] ?? 0) - ($t['tmp'] ?? 0));
                $pending = (bool) ($t['midtrans_pending'] ?? false);

                return $midtransOn && $sisa > 0 && ! $pending && ($t['status'] ?? null) !== 'Lunas';
            })
            ->values()
            ->all();
    }

    public function getSelectedTotalProperty(): int
    {
        $eligible = collect($this->batchEligible)->keyBy('kode_tagihan');
        $sum = 0;
        foreach ($this->selectedKodeTagihan as $kode) {
            $row = $eligible->get($kode);
            if ($row) {
                $sum += (int) (($row['jenis_tagihan']['jumlah'] ?? 0) - ($row['tmp'] ?? 0));
            }
        }

        return $sum;
    }

    public function toggleSelectAll(): void
    {
        $eligibleCodes = collect($this->batchEligible)->pluck('kode_tagihan')->all();
        $this->selectedKodeTagihan = count(array_intersect($this->selectedKodeTagihan, $eligibleCodes)) === count($eligibleCodes)
            ? []
            : $eligibleCodes;
    }

    public function hasSiblings(): bool
    {
        return count($this->siblings) > 0;
    }

    /**
     * Whether the Midtrans online payment feature is enabled.
     */
    public function isMidtransEnabled(): bool
    {
        return (bool) config('handayani.features.midtrans_enabled', false);
    }

    /**
     * Filament action for initiating an online payment via Midtrans.
     *
     * Per-row context (kode_tagihan, tagihan_name, sisa) is passed through the
     * `arguments` parameter when invoking the action from the blade view.
     */
    public function payAction(): Action
    {
        $minAmount = (int) config('handayani.midtrans.min_amount', 10000);
        $channels = $this->resolveFeeChannels();
        $defaultChannel = $this->resolveDefaultChannelKey($channels);

        // Index channels by key untuk dipakai di closure helperText.
        $channelByKey = collect($channels)->keyBy('key')->all();

        $channelOptions = collect($channels)
            ->mapWithKeys(fn (array $c): array => [
                $c['key'] => $c['label'].' — '.($c['description'] ?? ''),
            ])
            ->all();

        return Action::make('pay')
            ->label(__('midtrans.pay_online', [], 'id'))
            ->color('primary')
            ->size('xs')
            ->visible(fn () => PermissionHelper::hasResource('midtrans.pay'))
            ->modalHeading(__('midtrans.pay_online', [], 'id'))
            ->modalDescription(fn (array $arguments): string => $arguments['tagihan_name'] ?? '-')
            ->modalSubmitActionLabel(__('midtrans.confirm_payment', [], 'id'))
            ->modalCancelActionLabel(__('midtrans.cancel', [], 'id'))
            ->fillForm(fn (array $arguments) => [
                'amount_paid' => (int) ($arguments['sisa'] ?? 0),
                'payment_channel' => $defaultChannel,
            ])
            ->schema(fn (array $arguments): array => [
                TextInput::make('amount_paid')
                    ->label(__('midtrans.payment_amount', [], 'id'))
                    ->prefix('Rp')
                    ->numeric()
                    ->required()
                    ->minValue($minAmount)
                    ->maxValue((int) ($arguments['sisa'] ?? 0))
                    ->default((int) ($arguments['sisa'] ?? 0))
                    ->live(debounce: 300),

                Select::make('payment_channel')
                    ->label(__('midtrans.payment_channel', [], 'id'))
                    ->options($channelOptions)
                    ->default($defaultChannel)
                    ->required()
                    ->native(false)
                    ->searchable(false)
                    ->live()
                    ->helperText(function (Get $get) use ($channelByKey): HtmlString {
                        $amountPaid = (int) ($get('amount_paid') ?? 0);
                        $channel = (string) ($get('payment_channel') ?? '');
                        $fee = $this->computeFeeFromChannel($channelByKey[$channel] ?? null, $amountPaid);
                        $total = $amountPaid + $fee;

                        $feeLabel = __('midtrans.admin_fee', [], 'id');
                        $totalLabel = __('midtrans.total_payment', [], 'id');

                        return new HtmlString(
                            '<span class="block">'.e($feeLabel).': Rp '.number_format($fee, 0, ',', '.').'</span>'.
                            '<span class="block font-semibold text-primary-600 dark:text-primary-400">'.e($totalLabel).': Rp '.number_format($total, 0, ',', '.').'</span>'
                        );
                    }),
            ])
            ->action(function (array $data, array $arguments): void {
                $kodeTagihan = $arguments['kode_tagihan'] ?? null;
                $amountPaid = (int) ($data['amount_paid'] ?? 0);
                $channel = (string) ($data['payment_channel'] ?? '');

                if (! $kodeTagihan) {
                    Notification::make()
                        ->title(__('midtrans.TAGIHAN_NOT_FOUND', [], 'id'))
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $result = MidtransApi::initiate($kodeTagihan, $amountPaid, $channel);

                    $orderId = $result['data']['order_id'] ?? $result['order_id'] ?? null;
                    $redirectUrl = $result['data']['redirect_url'] ?? $result['redirect_url'] ?? null;

                    if (! $orderId) {
                        $this->notifyUnexpectedError();

                        return;
                    }

                    // Send the user to the Midtrans Snap page if we have one.
                    // Otherwise fall back to the internal status page so they
                    // can poll for the resolved transaction state.
                    if ($redirectUrl) {
                        $this->redirect($redirectUrl);

                        return;
                    }

                    $portalPath = config('handayani.portal.path', 'portal');
                    $this->redirect('/'.$portalPath.'/status-pembayaran?order_id='.urlencode($orderId));
                } catch (MidtransApiException $e) {
                    if ($e->errorCode === 'TAGIHAN_HAS_PENDING_TRANSACTION') {
                        // Chain into the continue confirmation modal, passing the
                        // pending transaction details as arguments.
                        $this->replaceMountedAction('continue', [
                            'order_id' => $e->data['order_id'] ?? null,
                            'snap_token' => $e->data['snap_token'] ?? null,
                            'redirect_url' => $e->data['redirect_url'] ?? null,
                        ]);

                        return;
                    }

                    Notification::make()
                        ->title($e->getUserMessage())
                        ->danger()
                        ->send();
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $this->notifyConnectionError();
                } catch (\Throwable $e) {
                    $this->notifyUnexpectedError();
                }
            });
    }

    /**
     * Fetch the per-channel fee list from the backend, falling back to a
     * single QRIS-default entry if the API call fails so the modal stays
     * usable offline.
     *
     * @return list<array{key:string,label:string,amount:int}>
     */
    private function resolveFeeChannels(): array
    {
        try {
            $response = MidtransApi::feeChannels();
            $channels = $response['data'] ?? [];

            if (! empty($channels)) {
                return $channels;
            }
        } catch (\Throwable $e) {
            // ignore and fall through to the local fallback
        }

        return [[
            'key' => 'qris',
            'label' => 'QRIS',
            'amount' => (int) config('handayani.midtrans.fee_flat', 4000),
        ]];
    }

    private function resolveDefaultChannelKey(array $channels): string
    {
        return $channels[0]['key'] ?? 'qris';
    }

    /**
     * Hitung admin fee untuk channel tertentu pada nominal $amountPaid.
     * Mendukung dua tipe fee dari backend: flat (`amount`) dan percent
     * (`percent` + optional `flat`).
     *
     * @param  array<string, mixed>|null  $channelData  hasil dari /midtrans/fee-channels
     */
    private function computeFeeFromChannel(?array $channelData, int $amountPaid): int
    {
        if ($channelData === null) {
            return 0;
        }

        $type = $channelData['type'] ?? 'flat';

        if ($type === 'percent') {
            $percent = (float) ($channelData['percent'] ?? 0);
            $flat = (int) ($channelData['flat'] ?? 0);

            return (int) round(($amountPaid * $percent / 100) + $flat);
        }

        // flat / legacy
        return (int) ($channelData['amount'] ?? $channelData['flat'] ?? 0);
    }

    /**
     * Filament confirmation action for continuing a pending Midtrans transaction.
     *
     * This action is opened via `replaceMountedAction('continue', [...])` from
     * inside `payAction()` when the backend reports an existing pending
     * transaction. It is not rendered as a standalone button in the blade view.
     */
    public function continueAction(): Action
    {
        return Action::make('continue')
            ->label(__('midtrans.continue_payment', [], 'id'))
            ->color('warning')
            ->visible(fn () => PermissionHelper::hasResource('midtrans.pay'))
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading(__('midtrans.TAGIHAN_HAS_PENDING_TRANSACTION', [], 'id'))
            ->modalDescription(__('midtrans.TAGIHAN_HAS_PENDING_TRANSACTION_CONTINUE', [], 'id'))
            ->modalSubmitActionLabel(__('midtrans.continue_payment', [], 'id'))
            ->modalCancelActionLabel(__('midtrans.cancel', [], 'id'))
            ->action(function (array $arguments): void {
                $orderId = $arguments['order_id'] ?? null;
                $redirectUrl = $arguments['redirect_url'] ?? null;

                if ($redirectUrl) {
                    $this->redirect($redirectUrl);

                    return;
                }

                if (! $orderId) {
                    $this->notifyUnexpectedError();

                    return;
                }

                $portalPath = config('handayani.portal.path', 'portal');
                $this->redirect('/'.$portalPath.'/status-pembayaran?order_id='.urlencode($orderId));
            });
    }

    /**
     * Resume a pending Midtrans transaction. Re-issues the initiation request:
     * the backend recognises the in-flight pending transaction and returns the
     * existing snap_token + redirect_url via the
     * `TAGIHAN_HAS_PENDING_TRANSACTION` continue path.
     */
    public function resumeAction(): Action
    {
        return Action::make('resume')
            ->label(__('midtrans.continue_payment', [], 'id'))
            ->color('warning')
            ->size('xs')
            ->visible(fn () => PermissionHelper::hasResource('midtrans.pay'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->action(function (array $arguments): void {
                $kodeTagihan = $arguments['kode_tagihan'] ?? null;
                $sisa = (int) ($arguments['sisa'] ?? 0);

                if (! $kodeTagihan || $sisa <= 0) {
                    $this->notifyUnexpectedError();

                    return;
                }

                try {
                    // Any valid amount triggers the in-flight detection on the
                    // backend; sisa is always within range.
                    MidtransApi::initiate($kodeTagihan, $sisa);

                    // If we somehow got a fresh transaction (no pending), fall
                    // back to the regular post-init redirect.
                    Notification::make()
                        ->title(__('midtrans.continue_payment', [], 'id'))
                        ->success()
                        ->send();
                } catch (MidtransApiException $e) {
                    if ($e->errorCode === 'TAGIHAN_HAS_PENDING_TRANSACTION') {
                        $redirectUrl = $e->data['redirect_url'] ?? null;
                        $orderId = $e->data['order_id'] ?? null;

                        if ($redirectUrl) {
                            $this->redirect($redirectUrl);

                            return;
                        }

                        if ($orderId) {
                            $portalPath = config('handayani.portal.path', 'portal');
                            $this->redirect('/'.$portalPath.'/status-pembayaran?order_id='.urlencode($orderId));

                            return;
                        }
                    }

                    Notification::make()
                        ->title($e->getUserMessage())
                        ->danger()
                        ->send();
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $this->notifyConnectionError();
                } catch (\Throwable $e) {
                    $this->notifyUnexpectedError();
                }
            });
    }

    public function render()
    {
        return view('livewire.tagihan-siswa');
    }

    /**
     * Filament action for paying multiple Tagihan in one Snap session.
     * Triggered by the sticky batch bar at the bottom of the page.
     */
    public function payBatchAction(): Action
    {
        $channels = $this->resolveFeeChannels();
        $defaultChannel = $this->resolveDefaultChannelKey($channels);
        $channelByKey = collect($channels)->keyBy('key')->all();

        $channelOptions = collect($channels)
            ->mapWithKeys(fn (array $c): array => [
                $c['key'] => $c['label'].' — '.($c['description'] ?? ''),
            ])
            ->all();

        return Action::make('payBatch')
            ->label(__('midtrans.pay_online', [], 'id'))
            ->color('primary')
            ->visible(fn () => PermissionHelper::hasResource('midtrans.pay'))
            ->modalHeading(__('midtrans.pay_online', [], 'id'))
            ->modalDescription(fn (): string => count($this->selectedKodeTagihan).' tagihan akan dibayar lunas.')
            ->modalSubmitActionLabel(__('midtrans.confirm_payment', [], 'id'))
            ->modalCancelActionLabel(__('midtrans.cancel', [], 'id'))
            ->fillForm(fn (): array => ['payment_channel' => $defaultChannel])
            ->schema([
                Select::make('payment_channel')
                    ->label(__('midtrans.payment_channel', [], 'id'))
                    ->options($channelOptions)
                    ->default($defaultChannel)
                    ->required()
                    ->native(false)
                    ->searchable(false)
                    ->live()
                    ->helperText(function (Get $get) use ($channelByKey): HtmlString {
                        $channel = (string) ($get('payment_channel') ?? '');
                        $fee = $this->computeFeeFromChannel($channelByKey[$channel] ?? null, $this->selectedTotal);
                        $total = $this->selectedTotal + $fee;

                        $sub = __('midtrans.payment_amount', [], 'id');
                        $feeLabel = __('midtrans.admin_fee', [], 'id');
                        $totalLabel = __('midtrans.total_payment', [], 'id');

                        return new HtmlString(
                            '<span class="block">'.e($sub).': Rp '.number_format($this->selectedTotal, 0, ',', '.').'</span>'.
                            '<span class="block">'.e($feeLabel).': Rp '.number_format($fee, 0, ',', '.').'</span>'.
                            '<span class="block font-semibold text-primary-600 dark:text-primary-400">'.e($totalLabel).': Rp '.number_format($total, 0, ',', '.').'</span>'
                        );
                    }),
            ])
            ->action(function (array $data): void {
                $kodeList = array_values(array_intersect(
                    $this->selectedKodeTagihan,
                    collect($this->batchEligible)->pluck('kode_tagihan')->all(),
                ));

                if (empty($kodeList)) {
                    Notification::make()
                        ->title('Tidak ada tagihan yang dipilih.')
                        ->danger()
                        ->send();

                    return;
                }

                $channel = (string) ($data['payment_channel'] ?? '');

                try {
                    $result = MidtransApi::initiateBatch($kodeList, $channel);

                    $orderId = $result['data']['order_id'] ?? $result['order_id'] ?? null;
                    $redirectUrl = $result['data']['redirect_url'] ?? $result['redirect_url'] ?? null;

                    if (! $orderId) {
                        $this->notifyUnexpectedError();

                        return;
                    }

                    if ($redirectUrl) {
                        $this->redirect($redirectUrl);

                        return;
                    }

                    $portalPath = config('handayani.portal.path', 'portal');
                    $this->redirect('/'.$portalPath.'/status-pembayaran?order_id='.urlencode($orderId));
                } catch (MidtransApiException $e) {
                    if ($e->errorCode === 'TAGIHAN_HAS_PENDING_TRANSACTION') {
                        $this->replaceMountedAction('continue', [
                            'order_id' => $e->data['order_id'] ?? null,
                            'snap_token' => $e->data['snap_token'] ?? null,
                            'redirect_url' => $e->data['redirect_url'] ?? null,
                        ]);

                        return;
                    }

                    Notification::make()
                        ->title($e->getUserMessage())
                        ->danger()
                        ->send();
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $this->notifyConnectionError();
                } catch (\Throwable $e) {
                    $this->notifyUnexpectedError();
                }
            });
    }
}
