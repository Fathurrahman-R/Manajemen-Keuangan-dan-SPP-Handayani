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
            'username' => 'selatpanjang',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'token' => 'test',
            'branch_id' => 1,
        ]);
        User::create([
            'username' => 'desakapur',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'token' => 'test2',
            'branch_id' => 2,
        ]);
    }
}
