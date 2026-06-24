<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleAttachDetachRequest;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use HttpResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return RoleResource::collection($roles);
    }

    public function store(RoleRequest $request)
    {
        // validasi data
        $data = $request->validated();

        // cek role sudah ada atau belum
        $existingRole = Role::query()->where('name', $data['name'])->first();
        if ($existingRole) {
            throw new HttpResponseException(response([
                'errors'=>[
                    'message' => ['Role dengan nama tersebut sudah ada.']
                ]
            ],400));
        }

        // buat role
        $role = Role::create(['name'=>$data['name']]);

        // asign permissions
        foreach ($data['permissions'] as $permission) {
            $role->givePermissionTo($permission);
        }

        // load permissions
        $role->refresh();
        $role->load('permissions');

        return new RoleResource($role);
    }

    public function show(int $id)
    {
        // cari role
        $role = Role::with('permissions')->find($id);

        // cek apakah data ada
        if (!$role) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'Role tidak ditemukan.'
                ]
            ],400));
        }
        return new RoleResource($role);
    }

    public function update(RoleRequest $request, int $id)
    {
        $data = $request->validated();
        $role = Role::query()->where('id', $id)->first();

        // cek apakah data ada
        if (!$role) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'Role tidak ditemukan.'
                ]
            ],400));
        }

        // update data
        $role->name = $data['name'];
        $role->syncPermissions($data['permissions']);
        $role->save();

        // load permissions
        $role->refresh();
        $role->load('permissions');

        return new RoleResource($role);
    }

    public function destroy(int $id)
    {
        $role = Role::query()->where('id', $id)->first();

        // cek apakah data ada
        if (!$role) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'Role tidak ditemukan.'
                ]
            ],400));
        }
        $role->delete();
        return new JsonResponse(['data'=>true]);
    }

    /**
     * Return the full list of available permissions, grouped by domain.
     *
     * Source of truth: `App\Constant\Permissions` + `App\Enum\Permission`.
     *
     * Output shape:
     * {
     *   "data": {
     *     "Users":         [ { "name": "view-user",         "label": "View User"         }, ... ],
     *     "Siswa":         [ { "name": "view-siswa",        "label": "View Siswa"        }, ... ],
     *     ...
     *   }
     * }
     */
    public function permissions(): JsonResponse
    {
        $groups = [
            'Users'              => \App\Constant\Permissions::USERS_PERMISSIONS,
            'Siswa'              => \App\Constant\Permissions::SISWA_PERMISSIONS,
            'Kelas'              => \App\Constant\Permissions::KELAS_PERMISSIONS,
            'Kategori'           => \App\Constant\Permissions::KATEGORI_PERMISSIONS,
            'Pembayaran'         => \App\Constant\Permissions::PEMBAYARAN_PERMISSIONS,
            'Jenis Tagihan'      => \App\Constant\Permissions::JENIS_TAGIHAN_PERMISSIONS,
            'Tagihan'            => \App\Constant\Permissions::TAGIHAN_PERMISSIONS,
            'Pengeluaran'        => \App\Constant\Permissions::PENGELUARAN_PERMISSIONS,
            'Approval Workflow'  => \App\Constant\Permissions::APPROVAL_WORKFLOW_PERMISSIONS,
            'Laporan'            => \App\Constant\Permissions::LAPORAN_PERMISSIONS,
            'Tahun Ajaran'       => \App\Constant\Permissions::TAHUN_AJARAN_PERMISSIONS,
            'Kenaikan Kelas'     => \App\Constant\Permissions::KENAIKAN_KELAS_PERMISSIONS,
            'Akun Siswa'         => \App\Constant\Permissions::AKUN_SISWA_PERMISSIONS,
            'Import Export'      => \App\Constant\Permissions::IMPORT_EXPORT_PERMISSIONS,
            'Dashboard'          => \App\Constant\Permissions::DASHBOARD_PERMISSIONS,
            'Branch'             => \App\Constant\Permissions::BRANCH_PERMISSIONS,
            'Midtrans'           => \App\Constant\Permissions::MIDTRANS_PERMISSIONS,
        ];

        // Roles & Permissions group lives only in the enum, not in Permissions constants.
        $rolesGroup = [
            \App\Enum\Permission::VIEW_ROLES,
            \App\Enum\Permission::CREATE_ROLE,
            \App\Enum\Permission::UPDATE_ROLE,
            \App\Enum\Permission::DELETE_ROLE,
            \App\Enum\Permission::ATTACH_ROLE,
            \App\Enum\Permission::DETACH_ROLE,
            \App\Enum\Permission::VIEW_PERMISSIONS,
            \App\Enum\Permission::ATTACH_PERMISSIONS,
            \App\Enum\Permission::DETACH_PERMISSIONS,
        ];

        $output = [];

        foreach ($groups as $label => $constant) {
            $output[$label] = $this->flattenPermissionGroup($constant);
        }

        $output['Roles & Permissions'] = array_map(
            fn(\App\Enum\Permission $p) => [
                'name'  => $p->value,
                'label' => $this->humanizePermission($p->value),
            ],
            $rolesGroup,
        );

        return response()->json(['data' => $output]);
    }

    /**
     * Flatten a permission constant array (which may contain nested arrays)
     * into a list of {name, label} entries.
     */
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
            'name'  => $name,
            'label' => $this->humanizePermission($name),
        ];
    }

    /**
     * Convert a kebab-case permission name into a human-readable label.
     */
    private function humanizePermission(string $name): string
    {
        return ucwords(str_replace('-', ' ', $name));
    }

    public function attach(RoleAttachDetachRequest $request)
    {
        $data = $request->validated();
        $user = User::query()->find($data['user_id']);
        if (!$user) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'User tidak ditemukan.'
                ]
            ],400));
        }
        $role = Role::query()->where('name', $data['role'])->first();
        if (!$role) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'Role tidak ditemukan.'
                ]
            ], 400));
        }
        $user->assignRole($role);
        return response()->json([
            'data'=>new UserResource($user),
            'message'=>'Role berhasil dikaitkan.'
        ]);
    }

    public function detach(RoleAttachDetachRequest $request)
    {
        $data = $request->validated();
        $user = User::query()->find($data['user_id']);
        if (!$user) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'User tidak ditemukan.'
                ]
            ],400));
        }
        $role = Role::query()->where('name', $data['role'])->first();
        if (!$role) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => 'Role tidak ditemukan.'
                ]
            ], 400));
        }
        $user->removeRole($role);
        return response()->json([
            'data'=>new UserResource($user),
            'message'=>'Role berhasil dilepaskan.'
        ]);
    }
}
