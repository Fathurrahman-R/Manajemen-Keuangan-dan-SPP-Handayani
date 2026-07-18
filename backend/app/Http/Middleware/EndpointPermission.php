<?php

namespace App\Http\Middleware;

use App\Models\PermissionEndpoint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EndpointPermission
{
    /**
     * Handle an incoming request.
     *
     * Middleware usage: ->middleware('endpoint.permission:siswa.view')
     *
     * Flow:
     * 1. Lookup resource_key in permission_endpoints table
     * 2. If mapping found with permission_id → check $user->can(permission_name)
     * 3. If mapping not found or permission_id is null → allow (not yet mapped)
     * 4. Superadmin always passes via Gate::before
     */
    public function handle(Request $request, Closure $next, string $resourceKey): Response
    {
        $endpoint = PermissionEndpoint::where('resource_key', $resourceKey)
            ->where('is_active', true)
            ->first();

        // Strict mode: if resource_key has no mapping, deny access
        if (! $endpoint) {
            abort(403, 'Forbidden: no permission mapping configured for resource "'.$resourceKey.'".');
        }

        // No permission bound yet → allow
        if (! $endpoint->permission_id) {
            return $next($request);
        }

        // Load permission relation if not already loaded
        $permissionName = $endpoint->permission?->name;

        // Permission record missing (e.g. deleted) → allow
        if (! $permissionName) {
            return $next($request);
        }

        // Check authorization via Laravel Gate (respects superadmin bypass)
        if ($request->user()?->can($permissionName)) {
            return $next($request);
        }

        abort(403, 'Forbidden: missing required permission "'.$permissionName.'" for resource "'.$resourceKey.'".');
    }
}
