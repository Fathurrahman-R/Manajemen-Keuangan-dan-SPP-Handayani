<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to deny access to users who only have the "siswa" role.
 * This provides defense-in-depth protection for admin panel routes,
 * ensuring siswa users cannot access any admin functionality even if
 * individual permission middleware is misconfigured or missing.
 */
class DenySiswaRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->hasOnlySiswaRole($user)) {
            return response()->json([
                'message' => 'Forbidden. Siswa accounts cannot access admin routes.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if the user has only the "siswa" role and no other roles.
     */
    private function hasOnlySiswaRole($user): bool
    {
        $roles = $user->getRoleNames();

        return $roles->count() === 1 && $roles->contains('siswa');
    }
}
