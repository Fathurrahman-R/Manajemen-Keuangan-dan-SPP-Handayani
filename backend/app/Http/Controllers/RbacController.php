<?php

namespace App\Http\Controllers;

use App\Models\PagePermission;
use App\Models\PermissionEndpoint;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacController extends Controller
{
    // ──────────────────────────────────────────────
    // Permissions CRUD
    // ──────────────────────────────────────────────

    public function indexPermissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get()->map(fn ($p) => [
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
            'name' => 'required|string|max:255|unique:Spatie\Permission\Models\Permission,name,'.$permission->id,
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
            'resource_key' => 'required|string|max:255|unique:permission_endpoints,resource_key,'.$endpoint->id,
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
            'resource_key' => 'required|string|max:255|unique:page_permissions,resource_key,'.$pagePermission->id,
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
    // Role CRUD (ported from RoleController)
    // ──────────────────────────────────────────────

    public function storeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name',
        ]);

        $existingRole = Role::query()->where('name', $validated['name'])->first();
        if ($existingRole) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['Role dengan nama tersebut sudah ada.']],
            ], 400));
        }

        $permissions = $validated['permissions'];
        $existingPermissions = Permission::whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();
        $missing = array_diff($permissions, $existingPermissions);
        if (! empty($missing)) {
            throw new HttpResponseException(response([
                'errors' => ['message' => ['Permission tidak valid: '.implode(', ', $missing)]],
            ], 400));
        }

        return DB::transaction(function () use ($validated, $permissions) {
            $role = Role::create(['name' => $validated['name']]);
            if (! empty($permissions)) {
                $role->syncPermissions($permissions);
            }
            $role->refresh();
            $role->load('permissions');

            return response()->json(['data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            ]], 201);
        });
    }

    public function showRole(int $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);
        if (! $role) {
            throw new HttpResponseException(response([
                'errors' => ['message' => 'Role tidak ditemukan.'],
            ], 400));
        }

        return response()->json(['data' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
        ]]);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::query()->where('id', $id)->first();
        if (! $role) {
            throw new HttpResponseException(response([
                'errors' => ['message' => 'Role tidak ditemukan.'],
            ], 400));
        }

        $role->name = $validated['name'];
        $role->syncPermissions($validated['permissions']);
        $role->save();

        $role->refresh();
        $role->load('permissions');

        return response()->json(['data' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
        ]]);
    }

    public function destroyRole(int $id): JsonResponse
    {
        $role = Role::query()->where('id', $id)->first();
        if (! $role) {
            throw new HttpResponseException(response([
                'errors' => ['message' => 'Role tidak ditemukan.'],
            ], 400));
        }
        $role->delete();

        return response()->json(['data' => true]);
    }

    /**
     * Return the full list of available permissions, grouped by domain.
     * Source of truth: `App\Constant\Permissions` + `App\Enum\Permission`.
     * Output shape matches RoleController.permissions() exactly.
     */
    public function permissionsTree(): JsonResponse
    {
        // ── Hardcoded group definitions ──
        $adminGroups = [

        ];

        $rbacGroup = [

        ];

        // ── Build the default section (audience = null) ──
        $defaultGroups = [];
        foreach ($adminGroups as $label => $constant) {
            $defaultGroups[$label] = $this->flattenPermissionGroup($constant);
        }

        $siswaHardcoded = [
//            'Tagihan & Pembayaran' => array_map(
//                fn (\App\Enum\Permission $p) => [
//                    'name' => $p->value,
//                    'label' => $this->humanizePermission($p->value),
//                ],
//                [
//                    \App\Enum\Permission::VIEW_OWN_BILLING,
//                    \App\Enum\Permission::PAY_TAGIHAN_ONLINE,
//                ],
//            ),
        ];

        // ── Collect all hardcoded names so we don't double-add ──
        $allHardcodedNames = collect($defaultGroups)
            ->flatten(1)
            ->pluck('name')
            ->merge(collect($siswaHardcoded)->flatten(1)->pluck('name'))
            ->values()
            ->toArray();

        // ── Dynamically add any permissions not in the hardcoded groups ──
        $allDbPerms = Permission::orderBy('name')->get();
        $orphaned = $allDbPerms->reject(fn ($p) => in_array($p->name, $allHardcodedNames));

        if ($orphaned->isNotEmpty()) {
            $dynamicGroups = [];
            foreach ($orphaned as $perm) {
                $groupName = $perm->group ?? 'Lainnya';
                if (! isset($dynamicGroups[$groupName])) {
                    $dynamicGroups[$groupName] = [];
                }
                $dynamicGroups[$groupName][] = [
                    'name' => $perm->name,
                    'label' => $perm->label ?: $this->humanizePermission($perm->name),
                ];
            }
            foreach ($dynamicGroups as $groupName => $entries) {
                $defaultGroups[$groupName] = $entries;
            }
        }

        // ── Build audiences ──
        // Collect all DB permissions with audience != null
        $audienceDbPermissions = $allDbPerms->reject(fn ($p) => $p->audience === null || $p->audience === '');
        $audiences = [
            'admin' => [
                'label' => 'Admin / Karyawan',
                'groups' => $defaultGroups,
            ],
        ];

        if ($audienceDbPermissions->isNotEmpty()) {
            $sectionGroups = [];
            foreach ($audienceDbPermissions as $perm) {
                $audienceKey = $perm->audience;
                $groupName = $perm->group ?: 'Lainnya';

                if ($audienceKey === 'siswa') {
                    if (! isset($sectionGroups[$groupName])) {
                        $sectionGroups[$groupName] = [];
                    }
                    $sectionGroups[$groupName][] = [
                        'name' => $perm->name,
                        'label' => $perm->label ?: $this->humanizePermission($perm->name),
                    ];
                }
            }

            // Merge hardcoded siswa permissions with dynamic ones
            foreach ($siswaHardcoded as $groupName => $entries) {
                if (isset($sectionGroups[$groupName])) {
                    $sectionGroups[$groupName] = array_merge($sectionGroups[$groupName], $entries);
                } else {
                    $sectionGroups[$groupName] = $entries;
                }
            }
            $audiences['siswa'] = [
                'label' => 'Siswa / Wali',
                'groups' => $sectionGroups,
            ];
        }

        return response()->json([
            'data' => [
                'audiences' => $audiences,
                ...$defaultGroups,
//                'Siswa Portal' => $siswaHardcoded['Tagihan & Pembayaran'],
            ],
        ]);
    }

    // ── Helper methods for permissions tree (ported from RoleController) ──

    private function flattenPermissionGroup(array $group): array
    {
        $result = [];
        foreach ($group as $value) {
            if (is_array($value)) {
                foreach ($value as $perm) {
                    $result[] = $this->permissionEntry($perm);
                }

                continue;
            }
            $result[] = $this->permissionEntry($value);
        }

        return $result;
    }

    private function permissionEntry(mixed $value): array
    {
        $name = $value instanceof \App\Enum\Permission ? $value->value : (string) $value;

        return [
            'name' => $name,
            'label' => $this->humanizePermission($name),
        ];
    }

    private function humanizePermission(string $name): string
    {
        $dict = $this->permissionLabelDictionary();
        if (isset($dict[$name])) {
            return $dict[$name];
        }

        $actionMap = [
            'view' => 'Lihat',
            'read' => 'Detail',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'manage' => 'Kelola',
            'attach' => 'Tetapkan',
            'detach' => 'Lepaskan',
            'approve' => 'Setujui',
            'disburse' => 'Cairkan',
            'sync' => 'Sinkronkan',
            'pay' => 'Bayar',
            'export' => 'Ekspor',
            'import' => 'Impor',
            'print' => 'Cetak',
        ];

        $resourceMap = [
            'user' => 'User',
            'users' => 'User',
            'siswa' => 'Siswa',
            'kelas' => 'Kelas',
            'kategori' => 'Kategori',
            'tagihan' => 'Tagihan',
            'jenis-tagihan' => 'Jenis Tagihan',
            'pembayaran' => 'Pembayaran',
            'pengeluaran' => 'Pengeluaran',
            'pengeluaran-request' => 'Permintaan Pengeluaran',
            'kas-harian' => 'Kas Harian',
            'rekap-bulanan' => 'Rekap Bulanan',
            'laporan' => 'Laporan',
            'roles' => 'Role',
            'role' => 'Role',
            'permissions' => 'Permission',
            'tahun-ajaran' => 'Tahun Ajaran',
            'kenaikan-kelas' => 'Kenaikan Kelas',
            'akun-siswa' => 'Akun Siswa',
            'data' => 'Data',
            'dashboard' => 'Dashboard',
            'own-billing' => 'Tagihan Sendiri',
            'tagihan-siswa' => 'Tagihan Siswa',
            'branch' => 'Cabang',
            'midtrans-transactions' => 'Transaksi Midtrans',
            'midtrans-config' => 'Konfigurasi Midtrans',
            'tagihan-online' => 'Tagihan Online',
            'kwitansi' => 'Kwitansi',
        ];

        $parts = explode('-', $name);
        if (count($parts) >= 2) {
            $action = array_shift($parts);
            $resourceKey = implode('-', $parts);

            if (isset($actionMap[$action]) && isset($resourceMap[$resourceKey])) {
                return $actionMap[$action].' '.$resourceMap[$resourceKey];
            }
        }

        return ucwords(str_replace('-', ' ', $name));
    }

    private function permissionLabelDictionary(): array
    {
        return [
            'view-permissions' => 'Lihat Daftar Permission',
            'attach-permissions' => 'Tetapkan Permission ke Role',
            'detach-permissions' => 'Lepaskan Permission dari Role',
            'attach-role' => 'Tetapkan Role ke User',
            'detach-role' => 'Lepaskan Role dari User',
            'pay-tagihan-online' => 'Bayar Tagihan Online',
            'view-midtrans-transactions' => 'Lihat Transaksi Midtrans',
            'sync-midtrans-transactions' => 'Sinkronkan Transaksi Midtrans',
            'view-midtrans-config' => 'Lihat Konfigurasi Midtrans',
            'update-midtrans-config' => 'Ubah Konfigurasi Midtrans',
            'view-own-billing' => 'Lihat Tagihan Sendiri',
            'view-tagihan-siswa' => 'Lihat Halaman Tagihan Siswa',
            'manage-akun-siswa' => 'Kelola Akun Siswa',
            'manage-tahun-ajaran' => 'Kelola Tahun Ajaran',
            'manage-kenaikan-kelas' => 'Kelola Kenaikan Kelas',
            'create-pengeluaran-request' => 'Ajukan Pengeluaran',
            'approve-pengeluaran' => 'Setujui Pengeluaran',
            'disburse-pengeluaran' => 'Cairkan Pengeluaran',
            'export-laporan' => 'Ekspor Laporan',
            'export-data' => 'Ekspor Data',
            'import-data' => 'Impor Data',
            'print-kwitansi' => 'Cetak Kwitansi',
            'view-dashboard' => 'Lihat Dashboard',
            'view-kas-harian' => 'Lihat Kas Harian',
            'view-rekap-bulanan' => 'Lihat Rekap Bulanan',
        ];
    }

    // ── Role attach/detach (ported from RoleController) ──

    // ──────────────────────────────────────────────
    // Original RbacController methods continued...
    // ──────────────────────────────────────────────

    public function indexRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'guard_name' => $r->guard_name,
            'permissions' => $r->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
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

        return response()->json([
            'data' => $resources,
            'debug' => [
                'user_id' => $user->id,
                'user_username' => $user->username,
                'user_branch' => $user->branch_id,
                'perms' => $userPermNames
            ]
        ]);
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
