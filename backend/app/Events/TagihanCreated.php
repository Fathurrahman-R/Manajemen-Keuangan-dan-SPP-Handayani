<?php

namespace App\Events;

use App\Models\Siswa;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TagihanCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Collection $tagihans,
        public Siswa $siswa
    ) {}
}
