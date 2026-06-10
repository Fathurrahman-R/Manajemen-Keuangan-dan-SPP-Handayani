<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\IdentifierService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly IdentifierService $identifierService
    ) {}

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

        // Backward compatible: use "identifier" if present, else fall back to "username"
        $identifier = $data['identifier'] ?? $data['username'] ?? '';

        $user = $this->identifierService->findUserByIdentifier($identifier);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['username or password is wrong']]
            ], 401));
        }

        // Check if account is active
        if ($user->is_active === false) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Akun tidak aktif. Hubungi admin sekolah.']]
            ], 401));
        }

        // Check if user already has an active token (delete expired tokens first)
        $user->tokens()->where('expires_at', '<', now())->delete();

        if ($user->tokens()->count() > 0) {
            // Force revoke all existing tokens to allow re-login
            $user->tokens()->delete();
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
                'email' => $user->email,
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at->toISOString(),
                'permissions' => $abilities,
                'roles' => $user->getRoleNames()->toArray(),
                'must_change_password' => $user->must_change_password,
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => true]);
    }
}
