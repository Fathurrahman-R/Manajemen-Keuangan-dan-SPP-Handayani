<?php

namespace App\Console\Commands;

use App\Models\PermissionEndpoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SyncEndpointsCommand extends Command
{
    protected $signature = 'permissions:sync-endpoints
        {--force : Update permission_id untuk endpoint yang sudah ada}';

    protected $description = 'Sync registered API routes into permission_endpoints table.';

    /**
     * Extract permission name from a route's action middleware array.
     * Looks for 'permission:xxx' patterns in ->middleware() calls.
     */
    private function extractPermissionNames(array $middleware): array
    {
        $perms = [];
        foreach ($middleware as $mw) {
            $mw = (string) $mw;
            if (Str::startsWith($mw, 'permission:')) {
                $name = Str::after($mw, 'permission:');
                // Handle pipe-separated: permission:perm1|perm2
                $perms = array_merge($perms, explode('|', $name));
            }
        }
        return array_unique($perms);
    }

    /**
     * Convert Laravel route URI (e.g. "api/users/{user}") to path_pattern.
     * Laravel's uri() already uses {param} syntax — matches our pattern format.
     */
    private function toPathPattern(string $uri): string
    {
        return '/'.trim($uri, '/');
    }

    /**
     * Infer group from the first segment of path.
     * e.g. "api/users/current" → "users"
     */
    private function inferGroup(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));
        // Skip 'api' prefix, take next meaningful segment
        $filtered = array_values(array_filter($segments, fn($s) => !in_array($s, ['api', 'v1'])));
        return $filtered[0] ?? null;
    }

    public function handle(): int
    {
        $routes = Route::getRoutes()->getRoutes();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();

            // Skip non-API routes
            if (!Str::startsWith($uri, 'api/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $permNames = $this->extractPermissionNames($middleware);

            // Skip routes without permission middleware — they don't need mapping
            // (but we still track them for visibility)
            if (empty($permNames)) {
                continue;
            }

            if (!in_array('auth:sanctum', $middleware)) {
                continue;
            }

            $path = $this->toPathPattern($uri);
            $group = $this->inferGroup($uri);

            foreach ($methods as $method) {
                $method = strtoupper($method);
                if (in_array($method, ['HEAD'])) {
                    continue;
                }

                $existing = PermissionEndpoint::where('method', $method)
                    ->where('path_pattern', $path)
                    ->first();

                if ($existing) {
                    if ($this->option('force') && !empty($permNames)) {
                        // Update permission_id based on first permission name
                        $perm = \Spatie\Permission\Models\Permission::where('name', $permNames[0])->first();
                        if ($perm && $existing->permission_id !== $perm->id) {
                            $existing->permission_id = $perm->id;
                            $existing->group = $group;
                            $existing->save();
                            $updated++;
                            $this->line("  ~ {$method} {$path} → {$permNames[0]}");
                        }
                    }
                    $skipped++;
                } else {
                    $permId = null;
                    if (!empty($permNames)) {
                        $perm = \Spatie\Permission\Models\Permission::where('name', $permNames[0])->first();
                        $permId = $perm?->id;
                    }

                    PermissionEndpoint::create([
                        'permission_id' => $permId,
                        'method' => $method,
                        'path_pattern' => $path,
                        'group' => $group,
                        'description' => !empty($permNames) ? "Auto-synced from route: {$permNames[0]}" : null,
                        'is_active' => true,
                    ]);
                    $created++;
                    $this->line("  + {$method} {$path} → ".($permNames[0] ?? '(unassigned)'));
                }
            }
        }

        // Clear cache
        Cache::forget('dynamic_permissions_endpoints');

        $this->info("Endpoint mapping selesai.");
        $this->info("  + {$created} baru");
        $this->info("  ~ {$updated} diupdate");
        $this->info("  = {$skipped} sudah ada (skip)");

        return self::SUCCESS;
    }
}
