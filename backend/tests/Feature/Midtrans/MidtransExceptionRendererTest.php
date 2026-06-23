<?php

namespace Tests\Feature\Midtrans;

use App\Exceptions\Midtrans\AmountBelowMinimumException;
use App\Exceptions\Midtrans\TagihanHasPendingTransactionException;
use App\Exceptions\Midtrans\TagihanNotFoundException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;

class MidtransExceptionRendererTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes that throw Midtrans exceptions
        Route::get('/_test/midtrans/not-found', function () {
            throw new TagihanNotFoundException('TGH-001');
        });

        Route::get('/_test/midtrans/below-minimum', function () {
            throw new AmountBelowMinimumException(5000, 10000);
        });

        Route::get('/_test/midtrans/pending-transaction', function () {
            throw new TagihanHasPendingTransactionException([
                'order_id' => 'HDY-TGH001-1234567890',
                'snap_token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
                'amount_paid' => 50000,
                'fee_amount' => 4000,
                'gross_amount' => 54000,
            ]);
        });
    }

    public function test_midtrans_exception_renders_json_with_error_code_and_message(): void
    {
        $response = $this->getJson('/_test/midtrans/not-found');

        $response->assertStatus(404)
            ->assertJson([
                'error_code' => 'TAGIHAN_NOT_FOUND',
                'message' => "Tagihan 'TGH-001' tidak ditemukan.",
            ]);
    }

    public function test_midtrans_exception_renders_correct_http_status(): void
    {
        $response = $this->getJson('/_test/midtrans/below-minimum');

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'AMOUNT_BELOW_MINIMUM',
            ]);
    }

    public function test_pending_transaction_exception_includes_data(): void
    {
        $response = $this->getJson('/_test/midtrans/pending-transaction');

        $response->assertStatus(409)
            ->assertJson([
                'error_code' => 'TAGIHAN_HAS_PENDING_TRANSACTION',
                'message' => 'Tagihan memiliki transaksi pending yang masih aktif.',
                'data' => [
                    'order_id' => 'HDY-TGH001-1234567890',
                    'snap_token' => 'snap-token-123',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
                    'amount_paid' => 50000,
                    'fee_amount' => 4000,
                    'gross_amount' => 54000,
                ],
            ]);
    }

    public function test_non_pending_transaction_exception_does_not_include_data_key(): void
    {
        $response = $this->getJson('/_test/midtrans/not-found');

        $response->assertStatus(404);
        $json = $response->json();
        $this->assertArrayNotHasKey('data', $json);
    }
}
