<?php

namespace App\Http\Middleware;

use App\Models\PagePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPagePermission
{
    /**
     * Handle an incoming request.
     * Check if the current request path matches any route_pattern in page_permissions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        $activeRules = PagePermission::where('is_active', true)->get();

        foreach ($activeRules as $rule) {
            if ($this->pathMatches($path, $rule->route_pattern)) {
                $user = $request->user();
                if (! $user || ! $user->can($rule->permission_name)) {
                    abort(403, 'Unauthorized — '.$rule->permission_name.' required.');
                }
                // Stop at first match.
                break;
            }
        }

        return $next($request);
    }

    /**
     * Simple glob matching: supports * as wildcard.
     * /admin/users* matches /admin/users, /admin/users/1, /admin/users/create
     */
    private function pathMatches(string $path, string $pattern): bool
    {
        // Normalise both sides.
        $pattern = '/'.trim($pattern, '/');
        $path = '/'.trim($path, '/');

        // Convert glob * to regex.
        $regex = '/^'.preg_replace('/\*/', '.*', preg_quote($pattern, '/')).'$/';

        return (bool) preg_match($regex, $path);
    }
}
