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
