<?php

use App\Services\ApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Regression test: deactivating a logged-in user's account revokes their
 * Sanctum tokens on the backend (see UserController::toggleActive()), but the
 * frontend session used to keep the old token forever — the user looked
 * "logged in" with zero permissions, and reactivating the account never
 * helped because no new token is ever issued without a fresh login.
 */
test('sesi dibersihkan otomatis saat backend menolak token dengan 401', function () {
    Http::fake([
        '*' => Http::response(['errors' => ['message' => ['Unauthenticated.']]], 401),
    ]);

    Session::put('data.token', 'revoked-token');
    Session::put('data.roles', ['admin']);
    Session::put('data.permissions', ['view-dashboard']);

    ApiService::client()->get('/rbac/user-resources');

    expect(Session::has('data.token'))->toBeFalse();
    expect(Session::has('data'))->toBeFalse();
});

test('sesi tidak diganggu saat backend merespons sukses', function () {
    Http::fake([
        '*' => Http::response(['data' => []], 200),
    ]);

    Session::put('data.token', 'valid-token');
    Session::put('data.roles', ['admin']);

    ApiService::client()->get('/rbac/user-resources');

    expect(Session::get('data.token'))->toBe('valid-token');
});

test('sesi tidak diganggu oleh 401 dari domain lain di luar backend api', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    Session::put('data.token', 'valid-token');

    Http::get('https://example.com/unrelated');

    expect(Session::get('data.token'))->toBe('valid-token');
});
