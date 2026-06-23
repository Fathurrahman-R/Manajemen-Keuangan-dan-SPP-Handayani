<?php

namespace App\Http\Controllers;

use App\Services\Midtrans\MidtransNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    /**
     * Handle Midtrans webhook notification.
     *
     * POST /api/midtrans/notification
     *
     * This controller does NOT check config('midtrans.enabled') —
     * webhooks must still process for existing transactions (Req 2.5).
     * The webhook_enabled flag is checked inside the service layer.
     */
    public function handle(Request $request, MidtransNotificationService $service): JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];
        $remoteIp = $request->ip();

        $result = $service->handle($payload, $rawBody, $remoteIp);

        if ($result->success) {
            return response()->json(['status' => 'ok'], 200);
        }

        return response()->json(['error_code' => $result->errorCode], $result->httpStatus);
    }
}
