<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Scoped to private/docker-internal ranges (covers Docker bridge networks and
        // typical LAN reverse-proxy setups) instead of '*' — trusting every peer as a
        // proxy would let anyone reaching this server directly spoof X-Forwarded-Host
        // and poison generated URLs (password reset links, signed URLs).
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ], headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Saat backend mencabut token (mis. login dari perangkat lain), sesi
        // dibersihkan di tengah request — halaman yang sedang dirender lalu
        // gagal otorisasi dan berakhir sebagai layar "403 Forbidden" telanjang,
        // tanpa petunjuk bahwa yang sebenarnya terjadi adalah sesi berakhir.
        // Kalau tidak ada token lagi di sesi, arahkan ke halaman login.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson() || session()->has('data.token')) {
                return null;
            }

            // Livewire menukar binding `redirect` dengan Redirector miliknya
            // sendiri, yang bukan objek Response. Mengembalikannya dari sini
            // membuat middleware CSRF gagal dengan "Undefined property:
            // Redirector::$headers" — 500, bukan halaman login. Jadi susun
            // RedirectResponse-nya langsung.
            session()->put('url.intended', $request->fullUrl());

            return new RedirectResponse('/login');
        });

        // Sama seperti AccessDeniedHttpException di atas, tapi untuk jalur lain
        // ke skenario yang sama: AppServiceProvider melempar AuthenticationException
        // begitu backend menolak token sesi dengan 401. Tanpa handler ini, Laravel
        // jatuh ke penanganan bawaannya yang juga memanggil helper `redirect()` —
        // kena Redirector Livewire yang sama, TypeError "Return value must be of
        // type Response, Redirector returned" di CustomAuthentication::handle().
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || session()->has('data.token')) {
                return null;
            }

            session()->put('url.intended', $request->fullUrl());

            return new RedirectResponse('/login');
        });
    })->create();
