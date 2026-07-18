<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Tests\TestCase;

use function PHPUnit\Framework\assertNotNull;

class UserTest extends TestCase
{
    public function test_register_success()
    {
        $token = $this->testLoginSuccess();

        $this->post(
            'api/users',
            [
                'username' => '3202316079',
                'password' => 'fathurrahman',
            ],
            [
                'Authorization' => $token,
            ]
        )->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'username',
                    'role',
                ],
            ]);
    }

    public function test_register_failed()
    {
        $token = $this->testLoginSuccess();
        $this->post(
            'api/users',
            [
                'username' => '',
                'password' => '',
            ],
            [
                'Authorization' => $token,
            ]
        )->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'username' => [
                        'The username field is required.',
                    ],
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ]);
    }

    public function test_register_username_already_exists()
    {
        $token = $this->testLoginSuccess();
        $this->post(
            'api/users',
            [
                'username' => 'admin',
                'password' => 'admin123',
            ],
            [
                'Authorization' => $token,
            ]
        )->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'username' => [
                        'Username already registered.',
                    ],
                ],
            ]);
    }

    public function test_register_failed_password_too_short()
    {
        $token = $this->testLoginSuccess();

        $this->post('api/users', [
            'username' => 'newuser',
            'password' => 'short', // < 8 char
        ], [
            'Authorization' => $token,
        ])->assertStatus(400)
            ->assertJsonStructure([
                'errors' => ['password'],
            ]);
    }

    public function test_login_success()
    {
        $this->seed(UserSeeder::class);
        $this->post('api/login', [
            'username' => 'handayaniselpa',
            'password' => 'admin123',
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'handayaniselpa',
                ],
            ]);

        $user = User::query()->where('username', 'handayaniselpa')->first();
        assertNotNull($user->token);

        return $user->token;
    }

    public function test_login_failed_username_not_found()
    {
        $this->post('api/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'username or password is wrong',
                    ],
                ],
            ]);
    }

    public function test_login_failed_password_wrong()
    {
        $this->seed(UserSeeder::class);
        $this->post('api/login', [
            'username' => 'handayaniselpa',
            'password' => 'salah123',
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'username or password is wrong',
                    ],
                ],
            ]);
    }

    public function test_login_failed()
    {
        $this->post('api/login', [
            'username' => '',
            'password' => '',
        ])->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'username' => [
                        'The username field is required.',
                    ],
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ]);
    }

    public function test_login_failed_password_too_short()
    {
        $this->post('api/login', [
            'username' => 'admin',
            'password' => 'short',
        ])->assertStatus(400)
            ->assertJsonStructure([
                'errors' => ['password'],
            ]);
    }

    public function test_get_success()
    {
        $this->seed(UserSeeder::class);
        $this->get('api/users/current', [
            'Authorization' => 'test',
        ])->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'admin',
                ],
            ]);
    }

    public function test_get_unauthorized()
    {
        $this->seed(UserSeeder::class);
        $this->get('api/users/current')
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'unauthorized.',
                    ],
                ],
            ]);
    }

    public function test_get_invalid_token()
    {
        $this->seed(UserSeeder::class);
        $this->get('api/users/current', [
            'Authorization' => 'salah',
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'unauthorized.',
                    ],
                ],
            ]);
    }

    public function test_update_password_success()
    {
        $this->seed(UserSeeder::class);
        $oldUser = User::where('username', 'admin')->first();

        $this->patch(
            'api/users/current',
            [
                'password' => 'admin456',
            ],
            [
                'Authorization' => 'test',
            ]
        )->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'admin',
                ],
            ]);

        $newUser = User::where('username', 'admin')->first();
        self::assertNotEquals($oldUser->password, $newUser->password);
    }

    public function test_update_failed()
    {
        $this->seed(UserSeeder::class);
        $this->patch(
            'api/users/current',
            [
                'password' => 'testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest',
            ],
            [
                'Authorization' => 'test',
            ]
        )->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'password' => [
                        'The password field must not be greater than 100 characters.',
                    ],
                ],
            ]);
    }

    public function test_logout_success()
    {
        $this->seed([UserSeeder::class]);
        $this->delete(uri: 'api/logout', headers: [
            'Authorization' => 'test',
        ])->assertStatus(200)
            ->assertJson([
                'data' => true,
            ]);

        $user = User::where('username', 'handayaniselpa')->first();
        self::assertNull($user->token);
    }

    public function test_logout_failed()
    {
        $this->seed(UserSeeder::class);
        $this->delete(uri: 'api/logout', headers: [
            'Authorization' => 'salah',
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'unauthorized.',
                    ],
                ],
            ]);
    }
}
