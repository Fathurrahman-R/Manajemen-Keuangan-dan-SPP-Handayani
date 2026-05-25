<?php

namespace Database\Seeders;

use App\Enum\DefaultRoles;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'username' => 'admin123',
            'name' => 'Admin',
            'password' => Hash::make('admin123'),
            'branch_id' => 964
        ]);
        $user->assignRole('superadmin');
        // Pastikan role admin sudah ada sebelum di-assign
        // $adminRole = Role::firstOrCreate(
        //     ['name' => DefaultRoles::ADMIN->value, 'guard_name' => 'web']
        // );

        // $users = [
        //     ['username' => 'handayaniselpa',   'token' => 'test',  'branch_id' => 1],
        //     ['username' => 'handayanideskap',  'token' => 'test2', 'branch_id' => 2],
        //     ['username' => 'handayanisiantan', 'token' => 'test3', 'branch_id' => 3],
        // ];

        // foreach ($users as $data) {
        //     $user = User::create([
        //         'username'  => $data['username'],
        //         'password'  => Hash::make('admin123'),
        //         'token'     => $data['token'],
        //         'branch_id' => $data['branch_id'],
        //     ]);
        //     $user->assignRole($adminRole);
        // }
    }
}
