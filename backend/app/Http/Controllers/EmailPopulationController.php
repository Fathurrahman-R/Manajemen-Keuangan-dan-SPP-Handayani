<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailPopulationController extends Controller
{
    public function __construct(
        private readonly EmailValidationService $emailValidationService
    ) {}

    /**
     * List admin/operator users without email in the current branch.
     */
    public function index(Request $request): JsonResponse
    {
        $branchId = $request->user()->branch_id;

        $users = User::where('branch_id', $branchId)
            ->whereNull('email')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'operator', 'superadmin']);
            })
            ->select('id', 'username', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $users]);
    }

    /**
     * Get email population progress for the branch.
     */
    public function progress(Request $request): JsonResponse
    {
        $branchId = $request->user()->branch_id;

        $total = User::where('branch_id', $branchId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'operator', 'superadmin']);
            })
            ->count();

        $populated = User::where('branch_id', $branchId)
            ->whereNotNull('email')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'operator', 'superadmin']);
            })
            ->count();

        $complete = $total > 0 && $populated >= $total;

        return response()->json([
            'data' => [
                'populated' => $populated,
                'total' => $total,
                'complete' => $complete,
                'message' => $complete
                    ? 'Semua akun admin/operator sudah memiliki email. Migrasi login siap diaktifkan.'
                    : "Masih ada " . ($total - $populated) . " akun yang belum memiliki email.",
            ],
        ]);
    }

    /**
     * Set email for a specific user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $branchId = $request->user()->branch_id;
        $email = $request->input('email');

        $user = User::where('id', $id)
            ->where('branch_id', $branchId)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        if (!$this->emailValidationService->isValidFormat($email)) {
            return response()->json([
                'errors' => ['email' => ['Format email tidak valid.']],
            ], 422);
        }

        if (!$this->emailValidationService->isUniqueInBranch($email, $branchId, $id)) {
            return response()->json([
                'errors' => ['email' => ['Email sudah digunakan oleh user lain di cabang ini.']],
            ], 422);
        }

        $user->email = $email;
        $user->save();

        return response()->json([
            'message' => 'Email berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }
}
