<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use function PHPUnit\Framework\assertNotNull;

class UserTest extends TestCase
{
    public function testRegisterSuccess()
    {
        $token = $this->testLoginSuccess();

        $this->post(
            "api/users",
            [
                'username' => '3202316079',
                'password' => 'fathurrahman'
            ],
            [
                'Authorization' => $token
            ]
        )->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'username',
                    'role',
                ],
            ]);
    }
    public function testRegisterFailed()
    {
        $token = $this->testLoginSuccess();
        $this->post(
            "api/users",
            [
                'username' => '',
                'password' => ''
            ],
            [
                'Authorization' => $token
            ]
        )->assertStatus(400)
            ->assertJson([
                "errors" => [
                    "username" => [
                        "The username field is required."
                    ],
                    "password" => [
                        "The password field is required."
                    ]
                ]
            ]);
    }
    public function testRegisterUsernameAlreadyExists()
    {
        $token = $this->testLoginSuccess();
        $this->post(
            "api/users",
            [
                'username' => 'admin',
                'password' => 'admin123'
            ],
            [
                'Authorization' => $token
            ]
        )->assertStatus(400)
            ->assertJson([
                "errors" => [
                    "username" => [
                        "Username already registered."
                    ]
                ]
            ]);
    }

    public function testRegisterFailedPasswordTooShort()
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

    public function testLoginSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->post("api/users/login", [
            'username' => 'admin',
            'password' => 'admin123',
        ])->assertStatus(200)
            ->assertJson([
                "data" => [
                    "username" => "admin"
                ]
            ]);

        $user = User::query()->where('username', 'admin')->first();
        assertNotNull($user->token);

        return $user->token;
    }

    public function testLoginFailedUsernameNotFound()
    {
        $this->post("api/users/login", [
            "username" => "admin",
            "password" => "admin123",
        ])->assertStatus(401)
            ->assertJson([
                "errors" => [
                    "message" => [
                        "username or password is wrong"
                    ]
                ]
            ]);
    }

    public function testLoginFailedPasswordWrong()
    {
        $this->seed(UserSeeder::class);
        $this->post("api/users/login", [
            "username" => "admin",
            "password" => "admin456",
        ])->assertStatus(401)
            ->assertJson([
                "errors" => [
                    "message" => [
                        "username or password is wrong"
                    ]
                ]
            ]);
    }

    public function testLoginFailed()
    {
        $this->post('api/users/login', [
            "username" => "",
            "password" => "",
        ])->assertStatus(400)
            ->assertJson([
                "errors" => [
                    "username" => [
                        "The username field is required."
                    ],
                    "password" => [
                        "The password field is required."
                    ]
                ]
            ]);
    }

    public function testLoginFailedPasswordTooShort()
    {
        $this->post('api/users/login', [
            'username' => 'admin',
            'password' => 'short',
        ])->assertStatus(400)
            ->assertJsonStructure([
                'errors' => ['password'],
            ]);
    }

    public function testGetSuccess()
    {
        $this->seed(UserSeeder::class);
        $this->get('api/users/current', [
            'Authorization' => 'test'
        ])->assertStatus(200)
            ->assertJson([
                "data" => [
                    "username" => "admin"
                ]
            ]);
    }

    public function testGetUnauthorized()
    {
        $this->seed(UserSeeder::class);
        $this->get('api/users/current')
            ->assertStatus(401)
            ->assertJson([
                "errors" => [
                    "message" => [
                        "unauthorized."
                    ]
                ]
            ]);
    }

    public function testGetInvalidToken()
    {
        $this->seed(UserSeeder::class);
        $this->get('api/users/current', [
            'Authorization' => 'salah'
        ])->assertStatus(401)
            ->assertJson([
                "errors" => [
                    "message" => [
                        "unauthorized."
                    ]
                ]
            ]);
    }

    public function testUpdatePasswordSuccess()
    {
        $this->seed(UserSeeder::class);
        $oldUser = User::where('username', 'admin')->first();

        $this->patch(
            'api/users/current',
            [
                "password" => "admin456",
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)
            ->assertJson([
                "data" => [
                    "username" => "admin"
                ]
            ]);

        $newUser = User::where("username", 'admin')->first();
        self::assertNotEquals($oldUser->password, $newUser->password);
    }

    public function testUpdateFailed()
    {
        $this->seed(UserSeeder::class);
        $this->patch(
            'api/users/current',
            [
                "password" => "testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest"
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(400)
            ->assertJson([
                "errors" => [
                    "password" => [
                        "The password field must not be greater than 100 characters."
                    ]
                ]
            ]);
    }

    public function testLogoutSuccess()
    {
        $this->seed([UserSeeder::class]);
        $this->delete(uri: 'api/users/logout', headers: [
            'Authorization' => 'test'
        ])->assertStatus(200)
            ->assertJson([
                "errors" => true
            ]);

        $user = User::where('username', 'admin')->first();
        self::assertNull($user->token);
    }

    public function testLogoutFailed()
    {
        $this->seed(UserSeeder::class);
        $this->delete(uri: 'api/users/logout', headers: [
            'Authorization' => 'salah'
        ])->assertStatus(401)
            ->assertJson([
                "errors" => [
                    "message" => [
                        "unauthorized."
                    ]
                ]
            ]);
    }
}
