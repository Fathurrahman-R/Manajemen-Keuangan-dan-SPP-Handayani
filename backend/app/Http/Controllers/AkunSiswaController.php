<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Services\AkunSiswaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AkunSiswaController extends Controller
{
    use Traits\Sortable;

    public function __construct(protected AkunSiswaService $akunSiswaService)
    {
    }

    /**
     * List all siswa accounts (users with "siswa" role) in admin's active branch.
     */
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 15)]
    #[QueryParameter('sort', description: 'Column to sort by (username, name, created_at)', required: false, example: 'username')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'asc')]
    public function index(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;

        $users = User::role('siswa')
            ->where('branch_id', $branchId)
            ->with('siswa');

        $this->applySorting($users, ['username', 'name', 'created_at'], 'username', 'asc');

        $users = $users->paginate($request->query('per_page', 15));

        return response()->json($users);
    }

    /**
     * List siswa without accounts (no User linked via siswa_id).
     * Supports ?jenjang= and ?kelas_id= query filters.
     */
    #[QueryParameter('per_page', description: 'Jumlah data per halaman', required: false, example: 15)]
    #[QueryParameter('sort', description: 'Column to sort by (nama, nis, kelas_id, created_at)', required: false, example: 'nama')]
    #[QueryParameter('direction', description: 'Sort direction (asc or desc)', required: false, example: 'asc')]
    public function unregistered(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;

        $query = Siswa::where('branch_id', $branchId)
            ->whereDoesntHave('user');

        if ($jenjang = $request->query('jenjang')) {
            $query->where('jenjang', strtoupper($jenjang));
        }

        if ($kelasId = $request->query('kelas_id')) {
            $query->where('kelas_id', (int) $kelasId);
        }

        $siswa = $query;

        $this->applySorting($siswa, ['nama', 'nis', 'kelas_id', 'created_at'], 'nama', 'asc');

        $siswa = $siswa->paginate($request->query('per_page', 15));

        return response()->json($siswa);
    }

    /**
     * Bulk create accounts for selected siswa IDs.
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $siswaIds = $request->validate([
            'siswa_ids' => 'required|array|min:1',
            'siswa_ids.*' => 'integer|exists:siswas,id',
        ])['siswa_ids'];

        $siswaList = Siswa::where('branch_id', $branchId)->whereIn('id', $siswaIds)->get();

        if ($siswaList->isEmpty()) {
            return response()->json(['errors' => ['siswa_ids' => ['Tidak ada siswa yang valid.']]], 422);
        }

        $result = $this->akunSiswaService->bulkCreateAccounts($siswaList);
        return response()->json(['data' => $result]);
    }

    /**
     * Reset password for a single siswa account.
     */
    public function resetPassword(int $id): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $user = User::role('siswa')->where('branch_id', $branchId)->where('id', $id)->first();

        if (!$user) {
            return response()->json(['errors' => ['message' => ['Akun tidak ditemukan.']]], 404);
        }

        $this->akunSiswaService->resetPassword($user);
        return response()->json(['message' => 'Password berhasil direset.']);
    }

    /**
     * Toggle active status for a siswa account.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $user = User::role('siswa')->where('branch_id', $branchId)->where('id', $id)->first();

        if (!$user) {
            return response()->json(['errors' => ['message' => ['Akun tidak ditemukan.']]], 404);
        }

        $user->update(['is_active' => !$user->is_active]);
        return response()->json(['data' => ['id' => $user->id, 'is_active' => $user->is_active]]);
    }

    /**
     * Return username and password pattern for selected accounts.
     */
    public function credentials(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $userIds = $request->query('ids', []);

        if (is_string($userIds)) {
            $userIds = explode(',', $userIds);
        }

        $users = User::role('siswa')
            ->where('branch_id', $branchId)
            ->whereIn('id', $userIds)
            ->with('siswa')
            ->get();

        $credentials = $users->map(fn($user) => [
            'id' => $user->id,
            'username' => $user->username,
            'password_pattern' => 'Tanggal lahir (DDMMYYYY)',
            'nama' => $user->siswa?->nama ?? $user->name,
        ]);

        return response()->json(['data' => $credentials]);
    }

    /**
     * Generate a printable PDF with credential cards for selected accounts.
     */
    public function credentialsPdf(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $userIds = $request->query('ids', []);

        if (is_string($userIds)) {
            $userIds = explode(',', $userIds);
        }

        $users = User::role('siswa')
            ->where('branch_id', $branchId)
            ->whereIn('id', $userIds)
            ->with('siswa')
            ->get();

        $credentials = $users->map(fn($user) => [
            'username' => $user->username,
            'password_pattern' => 'Tanggal lahir (DDMMYYYY)',
            'nama' => $user->siswa?->nama ?? $user->name,
        ])->toArray();

        $pdf = Pdf::loadView('credentials', ['credentials' => $credentials])
            ->setPaper('A4', 'portrait');

        return $pdf->download('kredensial-akun-siswa.pdf');
    }
}
