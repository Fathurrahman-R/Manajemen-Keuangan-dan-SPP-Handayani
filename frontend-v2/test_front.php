<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = new \App\Livewire\RoleManagement();
$refl = new ReflectionMethod($c, 'getPermissionAudiences');
$refl->setAccessible(true);
$res = $refl->invoke($c);
dump($res);
