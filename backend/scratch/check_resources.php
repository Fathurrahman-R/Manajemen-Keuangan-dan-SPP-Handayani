<?php

$base = dirname(__DIR__);

require $base.'/vendor/autoload.php';

$app = require_once $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$resources = DB::table('permission_resources')
    ->select('resource_key', 'label', 'group')
    ->get();

echo 'Total: '.$resources->count()." resources\n";
echo str_repeat('-', 60)."\n";

foreach ($resources as $r) {
    echo str_pad($r->resource_key, 35).' | '.str_pad($r->label, 20).' | '.($r->group ?? '-')."\n";
}
