<x-filament::page>
    <x-filament::tabs>
        @if(\App\Helpers\PermissionHelper::hasResource('permission.view'))
        <x-filament::tabs.item wire:click="$set('activeTab', 'permissions')" :active="$activeTab === 'permissions'" icon="heroicon-o-key">
            Permissions
        </x-filament::tabs.item>
        @endif
        
        @if(\App\Helpers\PermissionHelper::hasResource('role.view'))
        <x-filament::tabs.item wire:click="$set('activeTab', 'roles')" :active="$activeTab === 'roles'" icon="heroicon-o-shield-check">
            Assign Role
        </x-filament::tabs.item>
        @endif

        @if(\App\Helpers\PermissionHelper::hasResource('endpoint-mapping.view'))
        <x-filament::tabs.item wire:click="$set('activeTab', 'endpoints')" :active="$activeTab === 'endpoints'" icon="heroicon-o-link">
            Endpoint Mapping
        </x-filament::tabs.item>
        @endif

        @if(\App\Helpers\PermissionHelper::hasResource('resource-registry.view'))
        <x-filament::tabs.item wire:click="$set('activeTab', 'resources')" :active="$activeTab === 'resources'" icon="heroicon-o-cube">
            Resource & Page Registry
        </x-filament::tabs.item>
        @endif
    </x-filament::tabs>

    <div class="mt-6">
        @if($activeTab === 'permissions' && \App\Helpers\PermissionHelper::hasResource('permission.view'))
            @livewire(\App\Livewire\RbacPermissionsTable::class, key('perms-table'))
        @elseif($activeTab === 'roles' && \App\Helpers\PermissionHelper::hasResource('role.view'))
            @livewire(\App\Livewire\RoleManagement::class, key('role-mgmt'))
        @elseif($activeTab === 'endpoints' && \App\Helpers\PermissionHelper::hasResource('endpoint-mapping.view'))
            @livewire(\App\Livewire\RbacEndpointsTable::class, key('endpoints-table'))
        @elseif($activeTab === 'resources' && \App\Helpers\PermissionHelper::hasResource('resource-registry.view'))
            @livewire(\App\Livewire\RbacPagePermissionsTable::class, key('resources-table'))
        @endif
    </div>
</x-filament::page>
