<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\EmailValidationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use Traits\Sortable;

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

        $user->load(['branch', 'roles', 'siswa.ayah', 'siswa.ibu', 'siswa.wali']);

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
     *
     * @queryParam sort string Column to sort by (username, email, created_at). Example: username
     * @queryParam direction string Sort direction (asc or desc). Example: asc
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 10), 100);

        $query = User::with(['branch', 'roles'])->whereNull('siswa_id');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->query('role'));
            });
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('branch', function ($bq) use ($search) {
                      $bq->where('location', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $this->applySorting($query, ['username', 'email', 'name', 'created_at'], 'id', 'asc');

        $users = $query->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Create a new user with roles.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Block creation with superadmin role
        if (isset($data['roles']) && in_array('superadmin', $data['roles'])) {
            throw new HttpResponseException(response()->json([
                'errors' => ['roles' => ['Tidak dapat membuat user dengan role superadmin.']]
            ], 403));
        }

        $defaultPassword = $data['password'] ?? 'handayani123!';

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => Hash::make($defaultPassword),
            'branch_id' => $data['branch_id'],
            'is_active' => $data['is_active'] ?? true,
            'must_change_password' => true,
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

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }

        if (array_key_exists('name', $data)) {
            $user->name = $data['name'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['branch_id'])) {
            $user->branch_id = $data['branch_id'];
        }

        if (array_key_exists('is_active', $data)) {
            $user->is_active = (bool) $data['is_active'];
        }

        $user->save();

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        $user->load(['branch', 'roles']);

        return new UserResource($user);
    }

    /**
     * Send email verification OTP.
     */
    public function sendVerificationOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $user = Auth::user();
        $email = strtolower(trim($request->email));

        // Rate-limit: max 3 OTP requests per 10 minutes per user
        $rateLimitKey = 'otp_rate_' . $user->id;
        $attempts = (int) \Illuminate\Support\Facades\Cache::get($rateLimitKey, 0);
        if ($attempts >= 3) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'email' => ['Terlalu banyak permintaan OTP. Silakan coba lagi dalam beberapa menit.']
                ]
            ], 429));
        }
        \Illuminate\Support\Facades\Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(10));

        $emailService = app(EmailValidationService::class);
        if (!$emailService->isUniqueInBranch($email, $user->branch_id, $user->id)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'email' => ['Email sudah digunakan oleh user lain di cabang ini.']
                ]
            ], 422));
        }

        $otp = (string) random_int(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('email_otp_' . $user->id . '_' . $email, $otp, now()->addMinutes(10));

        \Illuminate\Support\Facades\Mail::send(
            'emails.verification-otp',
            ['otp' => $otp],
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Verifikasi Email - ' . config('app.name'));
            }
        );

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.',
        ]);
    }

    /**
     * Change the authenticated user's password (and optionally verify email).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $rules = [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ];

        $request->validate($rules);

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

    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->must_change_password) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Invalid action.']]
            ], 403));
        }

        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->email));
        $cachedOtp = \Illuminate\Support\Facades\Cache::get('email_otp_' . $user->id . '_' . $email);
        
        if (!$cachedOtp || (string)$cachedOtp !== (string)$request->otp) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'otp' => ['Kode OTP tidak valid atau sudah kadaluarsa.']
                ]
            ], 422));
        }
        
        $user->email = $email;
        $user->email_verified_at = now();
        $user->save();

        \Illuminate\Support\Facades\Cache::forget('email_otp_' . $user->id . '_' . $email);

        return response()->json([
            'data' => true,
            'message' => 'Email berhasil diverifikasi.'
        ]);
    }

    /**
     * Send OTP to a parent's (ayah/ibu/wali) email for verification.
     */
    public function sendWaliOtp(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:ayah,ibu,wali',
        ]);

        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Data siswa tidak ditemukan.']]
            ], 404));
        }

        $type = $request->type;
        $parent = match ($type) {
            'ayah' => $siswa->ayah,
            'ibu' => $siswa->ibu,
            'wali' => $siswa->wali,
        };

        if (!$parent || empty($parent->email)) {
            throw new HttpResponseException(response()->json([
                'errors' => ['email' => ['Data ' . $type . ' tidak memiliki email.']]
            ], 422));
        }

        $email = strtolower(trim($parent->email));

        // Rate limit: max 3 per 10 minutes
        $rateLimitKey = 'wali_otp_rate_' . $parent->id . '_' . $type;
        $attempts = \Illuminate\Support\Facades\Cache::get($rateLimitKey, 0);
        if ($attempts >= 3) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'email' => ['Terlalu banyak permintaan OTP. Silakan coba lagi dalam beberapa menit.']
                ]
            ], 429));
        }
        \Illuminate\Support\Facades\Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(10));

        $otp = (string) random_int(100000, 999999);
        $cacheKey = 'wali_otp_' . $parent->id . '_' . $type . '_' . $email;
        \Illuminate\Support\Facades\Cache::put($cacheKey, $otp, now()->addMinutes(10));

        \Illuminate\Support\Facades\Mail::send(
            'emails.verification-otp',
            ['otp' => $otp],
            function ($message) use ($email, $type) {
                $label = match ($type) {
                    'ayah' => 'Ayah',
                    'ibu' => 'Ibu',
                    'wali' => 'Wali',
                };
                $message->to($email)
                    ->subject('Verifikasi Email ' . $label . ' - ' . config('app.name'));
            }
        );

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email ' . $type . '.',
        ]);
    }

    /**
     * Verify OTP for a parent's (ayah/ibu/wali) email.
     */
    public function verifyWaliOtp(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:ayah,ibu,wali',
            'otp' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Data siswa tidak ditemukan.']]
            ], 404));
        }

        $type = $request->type;
        $parent = match ($type) {
            'ayah' => $siswa->ayah,
            'ibu' => $siswa->ibu,
            'wali' => $siswa->wali,
        };

        if (!$parent || empty($parent->email)) {
            throw new HttpResponseException(response()->json([
                'errors' => ['email' => ['Data ' . $type . ' tidak memiliki email.']]
            ], 422));
        }

        $email = strtolower(trim($parent->email));
        $cacheKey = 'wali_otp_' . $parent->id . '_' . $type . '_' . $email;
        $cachedOtp = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$cachedOtp || (string)$cachedOtp !== (string)$request->otp) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'otp' => ['Kode OTP tidak valid atau sudah kadaluarsa.']
                ]
            ], 422));
        }

        $parent->email_verified_at = now();
        $parent->save();

        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        $label = match ($type) {
            'ayah' => 'Ayah',
            'ibu' => 'Ibu',
            'wali' => 'Wali',
        };

        return response()->json([
            'data' => true,
            'message' => 'Email ' . $label . ' berhasil diverifikasi.'
        ]);
    }

    /**
     * Toggle active flag for a user. Tidak mempengaruhi role/permission;
     * user yang inaktif akan ditolak login oleh middleware autentikasi.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['User tidak ditemukan.']]
            ], 404));
        }

        $user->is_active = ! (bool) $user->is_active;
        $user->save();

        // Kalau user dinonaktifkan, revoke semua token aktifnya.
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'is_active' => $user->is_active,
            ],
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

        // Block deletion of superadmin users
        if ($user->hasRole('superadmin')) {
            throw new HttpResponseException(response()->json([
                'errors' => ['message' => ['Akun superadmin tidak dapat dihapus.']]
            ], 403));
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

    /**
     * Update the authenticated user's email (requires current password).
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'current_password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'current_password' => ['Password saat ini tidak sesuai.']
                ]
            ], 422));
        }

        $emailService = app(EmailValidationService::class);

        if (!$emailService->isUniqueInBranch($request->email, $user->branch_id, $user->id)) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'email' => ['Email sudah digunakan oleh user lain di cabang ini.']
                ]
            ], 422));
        }

        $user->email = $request->email;
        $user->save();

        return response()->json([
            'message' => 'Email berhasil diperbarui.',
            'data' => ['email' => $user->email],
        ]);
    }

    /**
     * Get the authenticated user's email notification preferences.
     */
    public function getNotificationPreferences(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->email) {
            return response()->json([
                'data' => [
                    'tagihan_baru' => false,
                    'reminder' => false,
                    'kwitansi' => false,
                    'overdue' => false,
                ]
            ]);
        }

        $optOuts = \App\Models\EmailOptOut::where('email', $user->email)
            ->pluck('notification_type')
            ->toArray();

        // If 'all' is present in optOuts, all are false.
        // Otherwise, they are true unless explicitly present in optOuts.
        $isAllOptedOut = in_array('all', $optOuts);

        return response()->json([
            'data' => [
                'tagihan_baru' => $isAllOptedOut ? false : !in_array('tagihan_baru', $optOuts),
                'reminder' => $isAllOptedOut ? false : !in_array('reminder', $optOuts),
                'kwitansi' => $isAllOptedOut ? false : !in_array('kwitansi', $optOuts),
                'overdue' => $isAllOptedOut ? false : !in_array('overdue', $optOuts),
            ]
        ]);
    }

    /**
     * Update the authenticated user's email notification preferences.
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->email) {
            throw new HttpResponseException(response()->json([
                'errors' => [
                    'message' => ['User belum mengatur email.']
                ]
            ], 422));
        }

        $data = $request->validate([
            'tagihan_baru' => 'required|boolean',
            'reminder' => 'required|boolean',
            'kwitansi' => 'required|boolean',
            'overdue' => 'required|boolean',
        ]);

        $types = ['tagihan_baru', 'reminder', 'kwitansi', 'overdue'];
        
        // Remove 'all' just in case it exists to normalize
        \App\Models\EmailOptOut::where('email', $user->email)->where('notification_type', 'all')->delete();

        foreach ($types as $type) {
            if ($data[$type] === false) {
                // User wants to opt-out
                \App\Models\EmailOptOut::firstOrCreate(
                    ['email' => $user->email, 'notification_type' => $type],
                    ['token' => \Illuminate\Support\Str::random(32)]
                );
            } else {
                // User wants to opt-in
                \App\Models\EmailOptOut::where('email', $user->email)
                    ->where('notification_type', $type)
                    ->delete();
            }
        }

        return response()->json([
            'message' => 'Preferensi notifikasi berhasil diperbarui.',
            'data' => $data,
        ]);
    }
}
