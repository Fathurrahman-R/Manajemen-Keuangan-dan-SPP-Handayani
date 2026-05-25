<?php

namespace App\Events;

use App\Models\Pembayaran;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PembayaranRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Pembayaran $pembayaran
    ) {}
}
