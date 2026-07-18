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
     * Usage: ApiService::client()->get('/rbac/roles')
     */
    public static function client(): PendingRequest
    {
        $token = session()->get('data.token');
        
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        if (session()->has('active_branch_id')) {
            $headers['X-Branch-Id'] = session('active_branch_id');
        }

        return Http::withHeaders($headers)->baseUrl(env('API_URL'));
    }
}
