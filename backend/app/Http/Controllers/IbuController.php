<?php

namespace App\Http\Controllers;

use App\Models\Ibu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IbuController extends Controller
{
    public function index(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $search = $request->query('search');

        $query = Ibu::whereHas('siswa', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        $ibus = $query->limit(20)->get();

        return response()->json(['data' => $ibus]);
    }
}
