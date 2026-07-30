<?php

use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    // Reset cache statis antar test.
    (function () {
        static::$userResources = null;
        static::$userGroups = null;
    })->call(new class extends PermissionHelper {});

    session()->put('data.token', 'token-valid');
});

test('hasResource memakai daftar resource dari RBAC API', function () {
    Http::fake(['*' => Http::response(['data' => ['siswa.view', 'kategori.view']], 200)]);

    expect(PermissionHelper::hasResource('siswa.view'))->toBeTrue();
    expect(PermissionHelper::hasResource('kelas.view'))->toBeFalse();
});

/**
 * Fallback saat resource registry kosong: helper jatuh ke daftar permission
 * mentah di session, supaya panel tidak ikut mati sebelum registry diisi.
 */
test('hasResource jatuh ke permission session saat registry kosong', function () {
    Http::fake(['*' => Http::response(['data' => []], 200)]);
    session()->put('data.permissions', ['view-siswa']);

    expect(PermissionHelper::hasResource('view-siswa'))->toBeTrue();
    expect(PermissionHelper::hasResource('view-kelas'))->toBeFalse();
});

test('hasAnyInGroup benar saat grup punya resource', function () {
    Http::fake(['*' => Http::response(['data' => [
        ['group' => 'akademik', 'resources' => ['siswa.view']],
        ['group' => 'keuangan', 'resources' => []],
    ]], 200)]);

    expect(PermissionHelper::hasAnyInGroup('akademik'))->toBeTrue();
    expect(PermissionHelper::hasAnyInGroup('keuangan'))->toBeFalse();
    expect(PermissionHelper::hasAnyInGroup('laporan'))->toBeFalse();
});
