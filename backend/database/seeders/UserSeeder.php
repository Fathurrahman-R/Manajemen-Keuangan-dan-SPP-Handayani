<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::first();
        if (! $mainBranch) {
            return;
        }

        $password = Hash::make('!handayani123');

        $superadmin = User::firstOrCreate(['username' => 'superadmin'], [
            'email' => 'superadmin@handayani.com',
            'name' => 'Superadmin',
            'password' => $password,
            'branch_id' => $mainBranch->id,
        ]);
        $superadmin->assignRole('superadmin');

        $developer = User::firstOrCreate(['username' => 'developer'], [
            'email' => 'developer@handayani.com',
            'name' => 'Developer',
            'password' => $password,
            'branch_id' => $mainBranch->id,
        ]);
        $developer->assignRole('developer');

        $yayasan = User::firstOrCreate(['username' => 'yayasan'], [
            'email' => 'yayasan@handayani.com',
            'name' => 'Kepala Yayasan',
            'password' => $password,
            'branch_id' => $mainBranch->id,
        ]);
        $yayasan->assignRole('kepala-yayasan');

        foreach (Branch::all() as $branch) {
            $admin = User::firstOrCreate(['username' => 'admin_'.strtolower(str_replace(' ', '_', $branch->location))], [
                'name' => 'Admin '.$branch->location,
                'password' => $password,
                'branch_id' => $branch->id,
            ]);
            $admin->assignRole('admin');
        }
    }
}
