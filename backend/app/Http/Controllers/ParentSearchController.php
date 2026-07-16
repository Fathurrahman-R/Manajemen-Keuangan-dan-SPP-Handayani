<?php

namespace App\Http\Controllers;

use App\Models\Ayah;
use App\Models\Ibu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Generic search endpoint for parent entities (Ayah, Ibu).
 *
 * Both Ayah and Ibu had identical controllers (search by `nama`, scope to current
 * branch via `siswa` relation, limit 20). This single controller serves both
 * routes via the {kind} parameter.
 */
class ParentSearchController extends Controller
{
    /**
     * Map between route segment and Eloquent model class.
     */
    private const MODELS = [
        'ayah' => Ayah::class,
        'ibu' => Ibu::class,
    ];

    public function index(Request $request, string $kind): JsonResponse
    {
        if (! isset(self::MODELS[$kind])) {
            return response()->json(['data' => []], 404);
        }

        /** @var class-string<Model> $model */
        $model = self::MODELS[$kind];
        $branchId = Auth::user()->branch_id;
        $search = $request->query('search');

        $query = $model::whereHas('siswa', function ($q) {});

        if ($search) {
            $query->where('nama', 'like', '%'.$search.'%');
        }

        return response()->json(['data' => $query->limit(20)->get()]);
    }

    /**
     * Convenience entry point used by the existing `/ayah` route.
     */
    public function ayah(Request $request): JsonResponse
    {
        return $this->index($request, 'ayah');
    }

    /**
     * Convenience entry point used by the existing `/ibu` route.
     */
    public function ibu(Request $request): JsonResponse
    {
        return $this->index($request, 'ibu');
    }

    /**
     * Get single Ayah by ID
     */
    public function showAyah($id): JsonResponse
    {
        $ayah = Ayah::find($id);
        if (! $ayah) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $ayah]);
    }

    /**
     * Get single Ibu by ID
     */
    public function showIbu($id): JsonResponse
    {
        $ibu = Ibu::find($id);
        if (! $ibu) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $ibu]);
    }
}
