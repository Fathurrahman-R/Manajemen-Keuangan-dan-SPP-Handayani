<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
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

    public function test_login_failed_inactive_account_shows_specific_message(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'nonaktif_user',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'is_active' => false,
        ]);

        $this->post('api/login', [
            'username' => 'nonaktif_user',
            'password' => 'admin123',
        ])->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'Akun tidak aktif. Hubungi admin sekolah.',
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

    public function test_verify_email_otp_success_for_admin_not_forced_password_change()
    {
        // Admin biasa yang sudah pernah ganti password (must_change_password = false),
        // memperbarui email lewat halaman profil, lalu verifikasi dengan OTP benar.
        $admin = User::factory()->admin()->create([
            'must_change_password' => false,
        ]);
        Sanctum::actingAs($admin, $admin->getAllPermissions()->pluck('name')->toArray());
        $email = 'admin-baru@example.com';

        $this->postJson('api/users/send-verification-otp', [
            'email' => $email,
        ])->assertStatus(200);

        $otp = Cache::get('email_otp_'.$admin->id.'_'.$email);
        self::assertNotNull($otp);

        $this->postJson('api/users/verify-email-otp', [
            'email' => $email,
            'otp' => $otp,
        ])->assertStatus(200)
            ->assertJson([
                'data' => true,
            ]);

        self::assertEquals($email, $admin->refresh()->email);
        self::assertNotNull($admin->email_verified_at);
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
