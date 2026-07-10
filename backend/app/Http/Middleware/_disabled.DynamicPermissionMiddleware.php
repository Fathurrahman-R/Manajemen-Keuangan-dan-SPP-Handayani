<?php

namespace App\Http\Middleware;

use App\Enum\DefaultRoles;
use App\Models\PermissionEndpoint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DynamicPermissionMiddleware
{
    /**
     * Cache duration for endpoint mapping (seconds).
     * Cleared when permission_endpoints table changes via observer/event.
     */
    private const CACHE_TTL = 3600;

    private const CACHE_KEY = 'dynamic_permissions_endpoints';

    /**
     * Handle an incoming request.
     *
     * 1. Superadmin → allow (bypass via Gate::before)
     * 2. Match method+path against permission_endpoints table
     * 3. If matched → check user has permission
     * 4. If no match → allow (permit by default for unmapped routes)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // User belum login — bypass, biar auth:sanctum yg handle 401
        if (! $user) {
            return $next($request);
        }

        // Superadmin bypass — Gate::before handle sisanya
        if ($user->hasRole(DefaultRoles::SUPERADMIN->value)) {
            return $next($request);
        }

        // Check endpoint mapping
        $endpoint = $this->matchEndpoint($request->method(), $request->path());

        if ($endpoint && $endpoint->permission_id) {
            if (! $user->can($endpoint->permission->name)) {
                abort(403, 'Unauthorized');
            }
        }

        return $next($request);
    }

    /**
     * Find the first matching endpoint for given method + path.
     */
    private function matchEndpoint(string $method, string $path): ?PermissionEndpoint
    {
        $endpoints = $this->getCachedEndpoints();

        $filtered = $endpoints->where('method', strtoupper($method));

        foreach ($filtered as $ep) {
            if ($this->pathMatches($path, $ep->path_pattern)) {
                return $ep;
            }
        }

        return null;
    }

    /**
     * Match a request path against a pattern with {param} placeholders.
     * Pattern "api/users/{id}" matches "api/users/123" but not "api/users/123/posts".
     */
    private function pathMatches(string $path, string $pattern): bool
    {
        // Normalize: both without leading slash
        $path = ltrim($path, '/');
        $pattern = ltrim($pattern, '/');

        $regex = preg_quote($pattern, '#');
        $regex = preg_replace('/\\\\\{[^}]+\}/', '[^/]+', $regex);
        $regex = '#^'.$regex.'$#i';

        return preg_match($regex, $path) === 1;
    }

    /**
     * Get cached endpoint list, or load from database.
     */
    private function getCachedEndpoints()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return PermissionEndpoint::with('permission')
                ->where('is_active', true)
                ->get();
        });
    }
}
