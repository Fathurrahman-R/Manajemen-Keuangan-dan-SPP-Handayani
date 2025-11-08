<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = User::where('role', $role)->first();
        if(!$user)
        {
            return response()->json([
                "errors"=>[
                    "message"=>[
                        "unauthorized."
                    ]
                ]
            ],401);
        }

        return $next($request);
    }
}
