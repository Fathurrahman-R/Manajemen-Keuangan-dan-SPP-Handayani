<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(UserRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (User::query()->where('username', $data['username'])->count() == 1) {
            throw new HttpResponseException(response([
                'errors' => [
                    'username' => [
                        'Username already registered.'
                    ]
                ]
            ], 400));
        }

        $user = new User($data);
        $user->password = Hash::make($data['password']);
        $user->save();

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function login(UserLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('username', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['username or password is wrong']]
            ], 401));
        }

        // Check if user already has an active token
        if ($user->tokens()->count() > 0) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Akun kamu sedang login di perangkat lain.']]
            ], 401));
        }

        // Gather all permission strings from user's roles
        $abilities = $user->getAllPermissions()->pluck('name')->toArray();

        // Create Sanctum token with abilities and expiration
        $expiration = config('sanctum.expiration', 480);
        $token = $user->createToken(
            'api-token',
            $abilities,
            now()->addMinutes($expiration)
        );

        return response()->json([
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at->toISOString(),
                'permissions' => $abilities,
                'roles' => $user->getRoleNames()->toArray(),
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => true]);
    }
}
