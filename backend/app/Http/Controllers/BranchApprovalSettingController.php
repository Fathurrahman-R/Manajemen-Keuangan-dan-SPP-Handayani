<?php

namespace App\Http\Controllers;

use App\Models\BranchApprovalSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchApprovalSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $branchId = $request->user()->branch_id;

        $settings = BranchApprovalSetting::firstOrCreate(
            ['branch_id' => $branchId],
            ['auto_approval_enabled' => false, 'auto_approval_threshold' => 0]
        );

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'auto_approval_enabled' => 'required|boolean',
            'auto_approval_threshold' => 'required|numeric|min:0',
        ]);

        $branchId = $request->user()->branch_id;

        $settings = BranchApprovalSetting::updateOrCreate(
            ['branch_id' => $branchId],
            $data
        );

        return response()->json(['data' => $settings]);
    }
}
