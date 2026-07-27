<?php

use App\Livewire\KenaikanKelas;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

/**
 * Regression for TA-006: KenaikanKelas.php checked
 * session('data.permissions') with in_array() directly instead of
 * PermissionHelper::hasResource() — superadmin's session has an empty
 * permissions array (bypass happens via backend Gate::before, not explicit
 * permission rows), so superadmin got a 403 here despite bypassing on every
 * other page that uses PermissionHelper::hasResource() correctly.
 */
test('superadmin can mount KenaikanKelas without an explicit permission row', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    Session::put('data.roles', ['superadmin']);
    Session::put('data.permissions', []);

    Livewire::test(KenaikanKelas::class)->assertOk();
});

test('user without kenaikan-kelas.view permission gets a 403', function () {
    Http::fake([
        '*/rbac/user-resources' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => []], 200),
    ]);

    Session::put('data.roles', ['admin']);
    Session::put('data.permissions', []);

    Livewire::test(KenaikanKelas::class)->assertForbidden();
});
