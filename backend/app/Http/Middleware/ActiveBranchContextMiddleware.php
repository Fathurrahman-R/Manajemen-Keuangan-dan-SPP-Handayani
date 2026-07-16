<?php

namespace App\Http\Middleware;

use App\Models\PermissionEndpoint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveBranchContextMiddleware
{
    /**
     * Handle an incoming request.
     * Intercepts X-Branch-Id and mutates the user's branch_id if they have permission.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestedBranchId = $request->header('X-Branch-Id');

        if ($requestedBranchId && $request->user() && $request->user()->branch_id != $requestedBranchId) {
            // Check if user has permission to switch branch via the abstract resource key 'api.branch.switch'
            $endpoint = PermissionEndpoint::where('resource_key', 'api.branch.switch')
                ->where('is_active', true)
                ->first();

            if ($endpoint && $endpoint->permission_id) {
                $permissionName = $endpoint->permission?->name;
                
                if ($permissionName && $request->user()->can($permissionName)) {
                    // Mutate the branch_id in memory for the duration of this request
                    $request->user()->branch_id = (int) $requestedBranchId;
                }
            }
        }

        return $next($request);
    }
}
