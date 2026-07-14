<?php

namespace App\Exceptions\Midtrans;

class InvalidMidtransConfigException extends MidtransException
{
    public string $errorCode = 'INVALID_MIDTRANS_CONFIG';

    public int $httpStatus = 500;

    public function __construct(string $variableName, mixed $value)
    {
        $displayValue = is_null($value) ? 'null' : "'{$value}'";

        parent::__construct(
            "Invalid Midtrans configuration: {$variableName} must be 'sandbox' or 'production', got {$displayValue}."
        );
    }
}
