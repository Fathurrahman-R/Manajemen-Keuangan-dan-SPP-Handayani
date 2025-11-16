<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'token' => 'test'
        ];
    }

    public function siswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => '000001',
            'role' => 'user',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
