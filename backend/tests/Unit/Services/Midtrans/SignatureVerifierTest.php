<?php

namespace Tests\Unit\Services\Midtrans;

use App\Services\Midtrans\SignatureVerifier;
use PHPUnit\Framework\TestCase;

class SignatureVerifierTest extends TestCase
{
    private SignatureVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new SignatureVerifier();
    }

    public function test_compute_returns_sha512_of_concatenated_values(): void
    {
        $orderId = 'HDY-TGH001-1234567890';
        $statusCode = '200';
        $grossAmount = '54000.00';
        $serverKey = 'SB-Mid-server-abc123';

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $actual = $this->verifier->compute($orderId, $statusCode, $grossAmount, $serverKey);

        $this->assertSame($expected, $actual);
        $this->assertSame(128, strlen($actual)); // SHA-512 hex is 128 chars
    }

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $serverKey = 'SB-Mid-server-test123';
        $orderId = 'HDY-TGH001-1234567890';
        $statusCode = '200';
        $grossAmount = '54000.00';

        $validSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
        ];

        $this->assertTrue($this->verifier->verify($payload, $serverKey));
    }

    public function test_verify_returns_false_for_invalid_signature(): void
    {
        $serverKey = 'SB-Mid-server-test123';

        $payload = [
            'order_id' => 'HDY-TGH001-1234567890',
            'status_code' => '200',
            'gross_amount' => '54000.00',
            'signature_key' => 'invalid-signature-value',
        ];

        $this->assertFalse($this->verifier->verify($payload, $serverKey));
    }

    public function test_verify_returns_false_when_signature_key_missing(): void
    {
        $serverKey = 'SB-Mid-server-test123';

        $payload = [
            'order_id' => 'HDY-TGH001-1234567890',
            'status_code' => '200',
            'gross_amount' => '54000.00',
            // signature_key missing
        ];

        $this->assertFalse($this->verifier->verify($payload, $serverKey));
    }

    public function test_verify_returns_false_when_any_field_tampered(): void
    {
        $serverKey = 'SB-Mid-server-test123';
        $orderId = 'HDY-TGH001-1234567890';
        $statusCode = '200';
        $grossAmount = '54000.00';

        $validSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // Tamper the gross_amount
        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => '99999.00', // tampered
            'signature_key' => $validSignature,
        ];

        $this->assertFalse($this->verifier->verify($payload, $serverKey));
    }
}
