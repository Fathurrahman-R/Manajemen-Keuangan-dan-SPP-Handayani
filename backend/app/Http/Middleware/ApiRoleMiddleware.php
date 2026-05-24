<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiRoleMiddleware
{
    /**
     * Cek apakah user yang sudah terautentikasi memiliki role yang diperlukan.
     * Harus digunakan setelah ApiAuthMiddleware (Auth::user() sudah tersedia).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // hasRole() dari spatie/laravel-permission
        if (!$user || !$user->hasRole($role)) {
            return response()->json([
                'errors' => [
                    'message' => ['Akses ditolak. Role yang dibutuhkan: ' . $role],
                ],
            ], 403);
        }

        return $next($request);
    }
}
