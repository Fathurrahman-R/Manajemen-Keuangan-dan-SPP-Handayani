<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationSettingRequest;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $setting = NotificationSetting::firstOrCreate(
            ['branch_id' => $branchId],
            [
                'tagihan_baru_enabled' => true,
                'reminder_enabled' => true,
                'kwitansi_enabled' => true,
                'overdue_enabled' => true,
                'reminder_days_before' => [7, 3, 1],
                'overdue_interval_days' => 7,
            ]
        );
        return response()->json(['data' => $setting]);
    }

    public function update(NotificationSettingRequest $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        $setting = NotificationSetting::firstOrCreate(
            ['branch_id' => $branchId],
            [
                'tagihan_baru_enabled' => true,
                'reminder_enabled' => true,
                'kwitansi_enabled' => true,
                'overdue_enabled' => true,
                'reminder_days_before' => [7, 3, 1],
                'overdue_interval_days' => 7,
            ]
        );
        $setting->update($request->validated());
        return response()->json(['data' => $setting]);
    }
}
