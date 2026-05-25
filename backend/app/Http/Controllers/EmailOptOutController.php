<?php

namespace App\Http\Controllers;

use App\Models\EmailOptOut;
use Illuminate\Http\Request;

class EmailOptOutController extends Controller
{
    public function show(string $token)
    {
        $optOut = EmailOptOut::where('token', $token)->first();

        if (!$optOut) {
            abort(404, 'Link tidak valid atau sudah kadaluarsa.');
        }

        return view('emails.unsubscribe', [
            'token' => $token,
            'email' => $optOut->email,
            'currentType' => $optOut->notification_type,
        ]);
    }

    public function update(Request $request, string $token)
    {
        $optOut = EmailOptOut::where('token', $token)->first();

        if (!$optOut) {
            abort(404, 'Link tidak valid atau sudah kadaluarsa.');
        }

        $type = $request->input('type', 'all');
        $validTypes = ['tagihan_baru', 'reminder', 'kwitansi', 'overdue', 'all'];

        if (!in_array($type, $validTypes)) {
            $type = 'all';
        }

        $optOut->update(['notification_type' => $type]);

        return view('emails.unsubscribe-confirmed', [
            'email' => $optOut->email,
            'type' => $type,
        ]);
    }
}
