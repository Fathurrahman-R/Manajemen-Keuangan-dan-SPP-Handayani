<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): ((\Symfony\Component\HttpFoundation\Response))  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If portal is disabled, abort any portal requests with 404
        $portalPath = config('handayani.portal.path', 'portal');
        if ($request->is($portalPath) || $request->is($portalPath.'/*')) {
            if (! config('handayani.features.portal_enabled', true)) {
                abort(404);
            }
        }

        $token = session()->get('data.token');

        if (is_null($token)) {
            return redirect()->intended('/login');
        }

        // Redirect to change-password page if must_change_password is true
        // (unless already on the change-password page)
        if (session()->get('data.must_change_password', false) && ! $request->is('*/change-password', 'change-password')) {
            if (PermissionHelper::hasResource('portal-access')) {
                return redirect()->to('/'.config('handayani.portal.path', 'portal').'/change-password');
            }

            return redirect()->to(filament()->getUrl().'/change-password');
        }

        // Prevent admin users (who lack portal-access) from accessing the portal
        if ($request->is($portalPath) || $request->is($portalPath.'/*')) {
            // Allow them to visit change-password if needed, but not other portal pages
            if (! PermissionHelper::hasResource('portal-access') && ! $request->is('*/change-password')) {
                return redirect()->to(filament()->getUrl().'/dashboard-page');
            }
        }

        return $next($request);
    }
}
