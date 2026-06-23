<?php

namespace App\Services;

use Exception;

class MidtransApiException extends Exception
{
    public function __construct(
        public readonly ?string $errorCode,
        public readonly mixed $data = null,
        public readonly int $httpStatus = 500,
    ) {
        $message = $errorCode
            ? __('midtrans.' . $errorCode, [], 'id')
            : 'Unknown Midtrans API error';

        parent::__construct($message, $httpStatus);
    }

    /**
     * Get the user-facing translated message for the error code.
     */
    public function getUserMessage(): string
    {
        if ($this->errorCode && trans()->has('midtrans.' . $this->errorCode, 'id')) {
            return __('midtrans.' . $this->errorCode, [], 'id');
        }

        return $this->getMessage();
    }
}
