<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Single source of truth for the RBAC seeder call order.
 *
 * Called from DatabaseSeeder (full seed) and directly from
 * docker/backend/entrypoint.sh (boot-time RBAC sync, replaces the removed
 * permissions:sync / permissions:sync-endpoints commands) — keeping both
 * entry points pointed at this one list avoids them drifting apart.
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            PermissionResourceSeeder::class,
            PermissionMetadataSeeder::class,
            PermissionEndpointSeeder::class,
        ]);
    }
}
