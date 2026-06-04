<?php

use App\Helpers\PermissionHelper;

uses(Tests\TestCase::class);

test('has returns true when user has specific permission', function () {
    session()->put('data.permissions', ['view-siswa', 'view-kategori']);

    expect(PermissionHelper::has('view-siswa'))->toBeTrue();
    expect(PermissionHelper::has('view-kelas'))->toBeFalse();
});

test('hasAnyInGroup returns true when user has at least one permission in group', function () {
    session()->put('data.permissions', ['view-kategori']);

    expect(PermissionHelper::hasAnyInGroup('akademik'))->toBeTrue();
    expect(PermissionHelper::hasAnyInGroup('keuangan'))->toBeFalse();
});

test('canViewJenjang returns true when no jenjang permissions exist at all (backwards compatibility)', function () {
    session()->put('data.permissions', ['view-siswa']); // No KB/TK/MI specific permissions

    expect(PermissionHelper::canViewJenjang('KB'))->toBeTrue();
    expect(PermissionHelper::canViewJenjang('TK'))->toBeTrue();
    expect(PermissionHelper::canViewJenjang('MI'))->toBeTrue();
});

test('canViewJenjang respects specific jenjang permissions when they exist', function () {
    session()->put('data.permissions', ['view-jenjang-kb']);

    expect(PermissionHelper::canViewJenjang('KB'))->toBeTrue();
    expect(PermissionHelper::canViewJenjang('TK'))->toBeFalse();
    expect(PermissionHelper::canViewJenjang('MI'))->toBeFalse();
});

test('visibleJenjang returns array of permitted jenjangs', function () {
    session()->put('data.permissions', ['view-jenjang-kb', 'view-jenjang-tk']);

    expect(PermissionHelper::visibleJenjang())->toEqual(['KB', 'TK']);
});
