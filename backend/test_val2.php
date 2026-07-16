<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = new \Illuminate\Http\Request();
$req->merge(['reminder_days_before' => [7, 3, 1]]);
$val = \Illuminate\Support\Facades\Validator::make($req->all(), [
    'reminder_days_before' => 'sometimes|array|min:1',
    'reminder_days_before.*' => 'integer|min:1|max:30'
]);
dump($val->fails());
dump($val->errors());
