<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'handayaniselpa',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'token' => 'test',
            'branch_id' => 1,
        ]);
        User::create([
            'username' => 'handayanideskap',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'token' => 'test2',
            'branch_id' => 2,
        ]);
        User::create([
            'username' => 'handayanisiantan',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'token' => 'test3',
            'branch_id' => 3,
        ]);
    }
}
