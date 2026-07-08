<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ApiService
{
    /**
     * Returns a pre-configured HTTP client with the Authorization Bearer token
     * from the current session and the base API URL.
     *
     * Usage: ApiService::client()->get('/roles')
     */
    public static function client(): PendingRequest
    {
        $token = session()->get('data.token');

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->baseUrl(env('API_URL'));
    }
}
