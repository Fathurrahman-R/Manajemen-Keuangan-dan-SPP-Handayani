<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionHelper;
use App\Models\PagePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFilamentPagePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if not authenticated — auth middleware handles that.
        if (! $request->user()) {
            return $next($request);
        }

        // Check each active rule — first match by resource_key wins
        $activeRules = PagePermission::where('is_active', true)->get();

        foreach ($activeRules as $rule) {
            // Priority: resource_key → permission_name
            if ($rule->resource_key) {
                if (! PermissionHelper::hasResource($rule->resource_key)) {
                    abort(403, 'Unauthorized — resource "' . $rule->resource_key . '" required.');
                }
            } elseif ($rule->permission_name) {
                $perms = session('data.permissions', []);
                if (! in_array($rule->permission_name, $perms, true)) {
                    abort(403, 'Unauthorized — ' . $rule->permission_name . ' required.');
                }
            }

            break; // First configured rule wins
        }

        return $next($request);
    }
}

