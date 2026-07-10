<?php

namespace App\Http\Controllers;

use App\Models\PagePermission;
use App\Models\PermissionEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacController extends Controller
{
    // ──────────────────────────────────────────────
    // Permissions CRUD
    // ──────────────────────────────────────────────

    public function indexPermissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'label' => $p->label,
            'guard_name' => $p->guard_name,
            'group' => $p->group,
            'audience' => $p->audience,
            'created_at' => $p->created_at,
        ]);

        return response()->json(['data' => $permissions]);
    }

    public function storePermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:Spatie\Permission\Models\Permission,name',
            'guard_name' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:100',
            'audience' => 'nullable|string|max:100',
            'label' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
            'group' => $validated['group'] ?? null,
            'audience' => $validated['audience'] ?? null,
            'label' => $validated['label'] ?? null,
        ]);

        return response()->json(['data' => $permission, 'message' => 'Permission created.'], 201);
    }

    public function updatePermission(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:Spatie\Permission\Models\Permission,name,' . $permission->id,
            'group' => 'nullable|string|max:100',
            'audience' => 'nullable|string|max:100',
            'label' => 'nullable|string|max:255',
        ]);

        $permission->update([
            'name' => $validated['name'],
            'group' => $validated['group'] ?? null,
            'audience' => $validated['audience'] ?? null,
            'label' => $validated['label'] ?? null,
        ]);

        return response()->json(['data' => $permission, 'message' => 'Permission updated.']);
    }

    public function destroyPermission(Permission $permission): JsonResponse
    {
        $permission->delete();

        return response()->json(['message' => 'Permission deleted.']);
    }

    // ──────────────────────────────────────────────
    // Endpoints CRUD (endpoint mapping — independent)
    // ──────────────────────────────────────────────

    public function indexEndpoints(): JsonResponse
    {
        $endpoints = PermissionEndpoint::with('permission')
            ->orderBy('resource_key')
            ->get();

        return response()->json(['data' => $endpoints]);
    }

    public function storeEndpoint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_key' => 'required|string|max:255|unique:permission_endpoints,resource_key',
            'permission_id' => 'nullable|exists:permissions,id',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $endpoint = PermissionEndpoint::create($validated);

        Cache::forget('dynamic_permissions_endpoints');

        return response()->json(['data' => $endpoint->load('permission'), 'message' => 'Endpoint created.'], 201);
    }

    public function updateEndpoint(Request $request, PermissionEndpoint $endpoint): JsonResponse
    {
        $validated = $request->validate([
            'resource_key' => 'required|string|max:255|unique:permission_endpoints,resource_key,' . $endpoint->id,
            'permission_id' => 'nullable|exists:permissions,id',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $endpoint->update($validated);

        Cache::forget('dynamic_permissions_endpoints');

        return response()->json(['data' => $endpoint->load('permission'), 'message' => 'Endpoint updated.']);
    }

    public function destroyEndpoint(PermissionEndpoint $endpoint): JsonResponse
    {
        $endpoint->delete();

        Cache::forget('dynamic_permissions_endpoints');

        return response()->json(['message' => 'Endpoint deleted.']);
    }

    // ──────────────────────────────────────────────
    // Page Permissions CRUD (merged Resource Registry + Page Security)
    // ──────────────────────────────────────────────

    public function indexPagePermissions(): JsonResponse
    {
        $resources = PagePermission::orderBy('resource_key')->get();

        return response()->json(['data' => $resources]);
    }

    public function storePagePermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource_key' => 'required|string|max:255|unique:page_permissions,resource_key',
            'permission_name' => 'nullable|string|max:255|exists:Spatie\Permission\Models\Permission,name',
            'guard_name' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $page = PagePermission::create([
            'resource_key' => $validated['resource_key'],
            'permission_name' => $validated['permission_name'] ?? null,
            'guard_name' => $validated['guard_name'] ?? 'web',
            'group' => $validated['group'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $page, 'message' => 'Resource created.'], 201);
    }

    public function updatePagePermission(Request $request, PagePermission $pagePermission): JsonResponse
    {
        $validated = $request->validate([
            'resource_key' => 'required|string|max:255|unique:page_permissions,resource_key,' . $pagePermission->id,
            'permission_name' => 'nullable|string|max:255|exists:Spatie\Permission\Models\Permission,name',
            'guard_name' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $pagePermission->update($validated);

        return response()->json(['data' => $pagePermission, 'message' => 'Resource updated.']);
    }

    public function destroyPagePermission(PagePermission $pagePermission): JsonResponse
    {
        $pagePermission->delete();

        return response()->json(['message' => 'Resource deleted.']);
    }

    // ──────────────────────────────────────────────
    // Role Assignment
    // ──────────────────────────────────────────────

    public function indexRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get()->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'guard_name' => $r->guard_name,
            'permissions' => $r->permissions->pluck('name'),
            'created_at' => $r->created_at,
        ]);

        return response()->json(['data' => $roles]);
    }

    public function getRolePermissions(Role $role): JsonResponse
    {
        return response()->json([
            'data' => [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function syncRolePermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json(['message' => 'Role permissions updated.', 'permissions' => $role->permissions->pluck('name')]);
    }

    // ──────────────────────────────────────────────
    // User Resources (for frontend PermissionHelper)
    // ──────────────────────────────────────────────

    public function userResources(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['data' => []]);
        }

        // Superadmin gets ALL active resource keys from page_permissions
        if ($user->hasRole('superadmin')) {
            $resources = PagePermission::where('is_active', true)
                ->pluck('resource_key');

            return response()->json(['data' => $resources]);
        }

        // Get user's permission names
        $userPermNames = $user->getAllPermissions()->pluck('name');

        // Find resources that match user's permissions
        $resources = PagePermission::where('is_active', true)
            ->whereIn('permission_name', $userPermNames)
            ->pluck('resource_key');

        return response()->json(['data' => $resources]);
    }

    public function userGroups(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['data' => []]);
        }

        // Superadmin sees all groups with all active resources
        if ($user->hasRole('superadmin')) {
            $resources = PagePermission::where('is_active', true)
                ->whereNotNull('group')
                ->orderBy('resource_key')
                ->get(['resource_key', 'group', 'description']);

            return response()->json(['data' => $this->groupResources($resources)]);
        }

        $userPermNames = $user->getAllPermissions()->pluck('name');

        $resources = PagePermission::where('is_active', true)
            ->whereNotNull('group')
            ->whereIn('permission_name', $userPermNames)
            ->orderBy('resource_key')
            ->get(['resource_key', 'group', 'description']);

        return response()->json(['data' => $this->groupResources($resources)]);
    }

    /**
     * Group resources by their group key.
     */
    protected function groupResources($resources): array
    {
        $grouped = [];
        foreach ($resources as $r) {
            $group = $r->group;
            if (! isset($grouped[$group])) {
                $grouped[$group] = [
                    'group' => $group,
                    'resources' => [],
                ];
            }
            $grouped[$group]['resources'][] = [
                'resource_key' => $r->resource_key,
                'description' => $r->description,
            ];
        }
        return array_values($grouped);
    }
}
