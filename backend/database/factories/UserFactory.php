<?php

namespace Database\Factories;

use App\Enum\DefaultRoles;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => 'admin',
            'password' => Hash::make('password'),
            'branch_id' => Branch::factory(),
        ];
    }

    /** User biasa (role: user) */
    public function siswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => '000001',
        ])->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => DefaultRoles::USER->value, 'guard_name' => 'web']);
            $user->assignRole($role);
        });
    }

    /** User dengan role admin */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => DefaultRoles::ADMIN->value, 'guard_name' => 'web']);
            $user->assignRole($role);
        });
    }

    /** User dengan role superadmin */
    public function superadmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => DefaultRoles::SUPERADMIN->value, 'guard_name' => 'web']);
            $user->assignRole($role);
        });
    }
}
