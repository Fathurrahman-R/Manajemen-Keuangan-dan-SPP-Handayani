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
        $token = session()->get('data');

        if(is_null($token)) {
            return redirect()->intended(filament()->getUrl() . '/login');
        }
        
        if(is_null($token['token'])) {
            return redirect()->intended(filament()->getUrl() . '/login');
        }

        return $next($request);
    }
}
