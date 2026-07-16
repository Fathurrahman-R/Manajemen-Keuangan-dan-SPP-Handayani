<?php

use App\Exceptions\Midtrans\MidtransException;
use App\Exceptions\Midtrans\TagihanHasPendingTransactionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'endpoint.permission' => \App\Http\Middleware\EndpointPermission::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active.branch' => \App\Http\Middleware\ActiveBranchContextMiddleware::class,
        ]);

        $middleware->api(prepend: [
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (MidtransException $e, Request $request) {
            $body = [
                'error_code' => $e->errorCode,
                'message' => $e->getMessage(),
            ];

            if ($e instanceof TagihanHasPendingTransactionException) {
                $body['data'] = $e->pendingData;
            }

            return response()->json($body, $e->httpStatus);
        });
    })->create();
