<?php

use App\Helpers\PermissionHelper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Backend yang mati dulu tampil sebagai "403 Forbidden": PermissionHelper
 * menelan kegagalan API dan mengembalikan daftar resource kosong, yang tidak
 * bisa dibedakan dari "user memang tidak berhak". Diagnosisnya jadi salah total.
 */
beforeEach(function () {
    // Reset cache statis antar test.
    (function () {
        static::$userResources = null;
        static::$userGroups = null;
    })->call(new class extends PermissionHelper {});
});

test('koneksi ke backend gagal menghasilkan 503, bukan 403', function () {
    Session::put('data.token', 'token-valid');
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    expect(fn () => PermissionHelper::getUserResources())
        ->toThrow(function (HttpException $e) {
            expect($e->getStatusCode())->toBe(503);
            expect($e->getMessage())->toContain('tidak dapat dihubungi');
        });
});

test('backend membalas status galat juga menghasilkan 503', function () {
    Session::put('data.token', 'token-valid');
    Http::fake(['*' => Http::response(['message' => 'Server Error'], 500)]);

    expect(fn () => PermissionHelper::getUserGroups())
        ->toThrow(fn (HttpException $e) => expect($e->getStatusCode())->toBe(503));
});

/**
 * Sesi yang dicabut backend (401) sudah dibersihkan listener di
 * AppServiceProvider. Hilangnya token berarti "sesi berakhir", bukan "server
 * mati" — pengguna harus diarahkan ke login, bukan disuguhi 503.
 */
test('sesi yang sudah dibersihkan diarahkan ke login, bukan 503', function () {
    Session::put('data.token', 'token-dicabut');
    Http::fake(['*' => Http::response(['errors' => ['message' => ['Unauthenticated.']]], 401)]);

    try {
        PermissionHelper::getUserResources();
        $this->fail('Seharusnya menghentikan request.');
    } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
        expect($e->getResponse()->getStatusCode())->toBe(302);
        expect($e->getResponse()->headers->get('Location'))->toContain('/login');
    }

    expect(Session::has('data.token'))->toBeFalse();
});

/**
 * Livewire mengganti binding `redirect` dengan Redirector miliknya sendiri, yang
 * bukan turunan Response. redirect()->guest() dari jalur ini karena itu lolos
 * pengecekan status tapi meledak di middleware CSRF: "Undefined property:
 * Livewire\Features\SupportRedirects\Redirector::$headers" — halaman login jadi 500.
 */
test('redirect ke login berupa Response sungguhan meski Livewire mengganti redirector', function () {
    Session::put('data.token', 'token-dicabut');
    Http::fake(['*' => Http::response(['errors' => ['message' => ['Unauthenticated.']]], 401)]);

    app()->bind('redirect', fn ($app) => new \Livewire\Features\SupportRedirects\Redirector(
        $app['url']
    ));

    try {
        PermissionHelper::getUserResources();
        $this->fail('Seharusnya menghentikan request.');
    } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
        expect($e->getResponse())->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
        expect($e->getResponse()->getStatusCode())->toBe(302);
    }
});

test('daftar resource kosong dari backend yang sehat tidak dianggap gagal', function () {
    Session::put('data.token', 'token-valid');
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    expect(PermissionHelper::getUserResources())->toBe([]);
});
