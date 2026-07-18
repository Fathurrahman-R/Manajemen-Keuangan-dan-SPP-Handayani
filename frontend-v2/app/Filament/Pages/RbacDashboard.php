<?php

namespace App\Filament\Pages;

use App\Helpers\PermissionHelper;
use App\Services\ApiService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RbacDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $title = 'RBAC Dashboard';

    protected static ?string $navigationLabel = 'Manajemen RBAC';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.rbac-dashboard';

    public string $activeTab = 'permissions';

    /** @var array<int, array{id: int, name: string, permissions: string[]}> */
    public array $rolesList = [];

    public ?int $selectedRoleId = null;

    public array $selectedRolePerms = [];

    public array $allPerms = [];

    public static function canAccess(): bool
    {
        return PermissionHelper::hasResource('rbac');
    }

    public function mount(): void
    {
        abort_if(! PermissionHelper::hasResource('rbac'), 403);

        if (PermissionHelper::hasResource('permission.view')) {
            $this->activeTab = 'permissions';
        } elseif (PermissionHelper::hasResource('role.view')) {
            $this->activeTab = 'roles';
        } elseif (PermissionHelper::hasResource('endpoint-mapping.view')) {
            $this->activeTab = 'endpoints';
        } elseif (PermissionHelper::hasResource('resource-registry.view')) {
            $this->activeTab = 'resources';
        }

        $this->reloadAll();
    }

    public function reloadAll(): void
    {
        $r = ApiService::client()->get('/rbac/roles');
        $this->rolesList = $r->successful() ? ($r->json()['data'] ?? []) : [];

        $r2 = ApiService::client()->get('/rbac/permissions');
        $this->allPerms = $r2->successful() ? ($r2->json()['data'] ?? []) : [];
    }

    // ── Role assignment ──

    public function selectRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $r = ApiService::client()->get("/rbac/roles/{$roleId}/permissions");
        $this->selectedRolePerms = $r->successful() ? ($r->json()['data']['permissions'] ?? []) : [];
    }

    public function toggleRolePerm(string $permName): void
    {
        if (in_array($permName, $this->selectedRolePerms)) {
            $this->selectedRolePerms = array_values(array_filter($this->selectedRolePerms, fn ($p) => $p !== $permName));
        } else {
            $this->selectedRolePerms[] = $permName;
        }
    }

    public function saveRolePerms(): void
    {
        if (! $this->selectedRoleId) {
            return;
        }
        ApiService::client()->put("/rbac/roles/{$this->selectedRoleId}/permissions", [
            'permissions' => $this->selectedRolePerms,
        ]);
        Notification::make()->title('Permission role disimpan.')->success()->send();
        $this->reloadAll();
    }
}
