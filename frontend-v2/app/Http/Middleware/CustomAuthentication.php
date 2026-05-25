<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = session()->get('data.token');

        if (is_null($token)) {
            return redirect()->intended(filament()->getUrl() . '/login');
        }

        // Redirect to change-password page if must_change_password is true
        // (unless already on the change-password page)
        if (session()->get('data.must_change_password', false) && !$request->routeIs('filament.admin.pages.change-password')) {
            return redirect()->to(filament()->getUrl() . '/change-password');
        }

        return $next($request);
    }
}
