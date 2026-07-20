<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        return Http::withHeaders($headers)->baseUrl(config('handayani.api_url'));
    }

    /**
     * GET an endpoint through Redis (cache-aside, TTL-bound). Returns the decoded
     * `data` payload, or null when the upstream request failed — callers decide
     * their own fallback, mirroring the existing `$response->ok()` pattern.
     *
     * Cache key is scoped per authenticated user + active branch so one user's
     * cached response can never leak into another user's/branch's view. A short
     * TTL (default `handayani.cache.dashboard_ttl`) means data self-refreshes on
     * the next widget poll without needing per-write invalidation.
     *
     * Usage: ApiService::cachedGet('/dashboard/summary', $params)
     */
    public static function cachedGet(string $endpoint, array $params = [], ?int $ttl = null): ?array
    {
        $ttl ??= (int) config('handayani.cache.dashboard_ttl', 60);

        return Cache::remember(
            self::dashboardCacheKey($endpoint, $params),
            $ttl,
            function () use ($endpoint, $params) {
                $response = self::client()->get($endpoint, $params);

                return $response->ok() ? ($response->json('data') ?? []) : null;
            }
        );
    }

    /**
     * Fetch one slice of the combined `/dashboard/overview` payload. Every
     * dashboard widget calling this with the SAME $params hits the SAME Redis
     * cache key (see `dashboardCacheKey()`) — only the first widget's call
     * actually performs the HTTP request; the rest are cache hits reading a
     * different key of the same cached array. This is what replaced 9 separate
     * per-widget endpoints (each paying its own Laravel bootstrap + round-trip)
     * with a single combined backend call.
     *
     * Usage: ApiService::dashboardOverviewSlice('summary', $params)
     */
    public static function dashboardOverviewSlice(string $key, array $params = []): ?array
    {
        $overview = self::cachedGet('/dashboard/overview', $params);

        if ($overview === null) {
            return null;
        }

        return $overview[$key] ?? [];
    }

    /**
     * Force every dashboard cache entry for the current user/branch scope to be
     * treated as stale, without needing to enumerate/forget individual keys.
     * Bumping the version changes every subsequent cache key, so old entries
     * are simply orphaned (and expire naturally via their own TTL).
     *
     * Usage: called from the dashboard's "Refresh" action.
     */
    public static function bustDashboardCache(): void
    {
        $versionKey = self::dashboardVersionKey();

        Cache::forever($versionKey, ((int) Cache::get($versionKey, 1)) + 1);
    }

    protected static function dashboardVersionKey(): string
    {
        return sprintf(
            'dashboard-cache-version:%s:%s',
            Auth::id() ?? 'guest',
            session('active_branch_id', 'none')
        );
    }

    protected static function dashboardCacheKey(string $endpoint, array $params): string
    {
        $version = (int) Cache::get(self::dashboardVersionKey(), 1);

        return sprintf(
            'dashboard-cache:%s:%s:v%d:%s',
            Auth::id() ?? 'guest',
            session('active_branch_id', 'none'),
            $version,
            md5($endpoint.serialize($params))
        );
    }
}
