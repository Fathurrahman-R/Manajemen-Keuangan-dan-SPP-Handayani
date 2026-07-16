<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('username', 'yayasan')->first();
$u->password = bcrypt('!handayani123');
$u->save();
echo "Password reset for yayasan.\n";
