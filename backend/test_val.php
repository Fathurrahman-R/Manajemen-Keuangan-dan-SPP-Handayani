<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = Spatie\Permission\Models\Role::with('permissions')->where('name', 'admin')->first();
$stale = [];
foreach ($role->permissions as $p) {
    if (!Spatie\Permission\Models\Permission::where('name', $p->name)->exists()) {
        $stale[] = $p->name;
    }
}
dump('Stale perms:');
dump($stale);
dump('All perms count:');
dump($role->permissions->count());
