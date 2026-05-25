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
