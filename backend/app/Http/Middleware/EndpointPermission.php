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

        // Sebelum menolak, pastikan penolakannya bukan cache Spatie yang basi.
        // `can()` membaca cache role→permission; kalau cache di-flush di store
        // yang berbeda (mis. seeder dijalankan dari host sementara aplikasi
        // jalan di container), setiap permission yang sah ikut ditolak dan
        // seluruh aplikasi terkunci sampai TTL 24 jam habis. Cek ulang ke
        // database sekali; kalau ternyata user memang punya permission itu,
        // buang cache lalu lanjutkan.
        if ($this->hasPermissionInDatabase($request, $permissionName)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            \Illuminate\Support\Facades\Log::warning('Cache permission basi terdeteksi; cache di-flush otomatis', [
                'user_id' => $request->user()?->id,
                'permission' => $permissionName,
                'resource_key' => $resourceKey,
            ]);

            return $next($request);
        }

        abort(403, 'Forbidden: missing required permission "'.$permissionName.'" for resource "'.$resourceKey.'".');
    }

    /**
     * Query langsung ke tabel pivot, melewati cache Spatie sepenuhnya.
     */
    private function hasPermissionInDatabase(Request $request, string $permissionName): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $viaRole = \Illuminate\Support\Facades\DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('model_has_roles', 'model_has_roles.role_id', '=', 'role_has_permissions.role_id')
            ->where('permissions.name', $permissionName)
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->exists();

        if ($viaRole) {
            return true;
        }

        return \Illuminate\Support\Facades\DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('permissions.name', $permissionName)
            ->where('model_has_permissions.model_id', $user->getKey())
            ->where('model_has_permissions.model_type', $user->getMorphClass())
            ->exists();
    }
}
