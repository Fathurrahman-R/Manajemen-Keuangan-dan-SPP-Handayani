<?php

namespace App\Http\Controllers;

use App\Models\Ayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AyahController extends Controller
{
    public function index(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $search = $request->query('search');

        $query = Ayah::whereHas('siswa', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        $ayahs = $query->limit(20)->get();

        return response()->json(['data' => $ayahs]);
    }
}
