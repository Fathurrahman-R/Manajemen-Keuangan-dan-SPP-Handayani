<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class FilamentDatabaseNotification extends DatabaseNotification
{
    protected $table = 'filament_notifications';
}
