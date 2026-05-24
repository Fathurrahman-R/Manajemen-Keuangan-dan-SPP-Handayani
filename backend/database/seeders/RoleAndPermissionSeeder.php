<?php

namespace Database\Seeders;

use App\Constant\PermissionBinding;
use App\Constant\Permissions;
use App\Enum\DefaultRoles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
//
//        $role = Role::create(['name'=>DefaultRoles::ADMIN]);
//
//        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
//
//        foreach (PermissionBinding::ADMIN_PERMISSIONS as $permissions) {
//            foreach ($permissions as $permission=>$value) {
//                Permission::create(['name'=>$value]);
//                $role->givePermissionTo($value);
//            }
//        }

        $user = User::query()->whereId(1)->first();
        $user->assignRole(DefaultRoles::ADMIN);
    }
}
