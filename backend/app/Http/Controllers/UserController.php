<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get the currently authenticated user's profile.
     */
    public function get(Request $request): UserResource
    {
        $user = Auth::user();
        if (!$user) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['unauthorized.']
                ]
            ], 401));
        }

        return new UserResource($user);
    }

    /**
     * Update the currently authenticated user's profile.
     */
    public function updateCurrent(UserUpdateRequest $request): UserResource
    {
        $data = $request->validated();
        $user = Auth::user();
        if (!$user) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => ['unauthorized.']
                ]
            ], 401));
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        return new UserResource($user);
    }

    /**
     * List users with pagination and optional filters.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 10), 100);

        $query = User::with(['branch', 'roles']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->query('role'));
            });
        }

        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Create a new user with roles.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'branch_id' => $data['branch_id'],
        ]);

        $user->syncRoles($data['roles']);
        $user->load(['branch', 'roles']);

        return response()->json([
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Show a specific user by ID.
     */
    public function show(int $id): UserResource
    {
        $user = User::with(['branch', 'roles'])->find($id);

        if (!$user) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['User tidak ditemukan.']]
            ], 404));
        }

        return new UserResource($user);
    }

    /**
     * Update a specific user by ID.
     */
    public function update(UserRequest $request, int $id): UserResource
    {
        $user = User::find($id);

        if (!$user) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['User tidak ditemukan.']]
            ], 404));
        }

        $data = $request->validated();

        if (isset($data['username'])) {
            $user->username = $data['username'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['branch_id'])) {
            $user->branch_id = $data['branch_id'];
        }

        $user->save();

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        $user->load(['branch', 'roles']);

        return new UserResource($user);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'current_password' => ['Password saat ini tidak sesuai.']
                ]
            ], 422));
        }

        $user->password = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'data' => true,
            'message' => 'Password berhasil diubah.'
        ]);
    }

    /**
     * Delete a user by ID.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['User tidak ditemukan.']]
            ], 404));
        }

        // Revoke all Sanctum tokens
        $user->tokens()->delete();

        // Remove all role assignments
        $user->syncRoles([]);

        // Delete user record
        $user->delete();

        return response()->json([
            'data' => true,
            'message' => 'User berhasil dihapus.'
        ]);
    }
}
