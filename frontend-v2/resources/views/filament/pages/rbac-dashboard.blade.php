<x-filament::page>
    <x-filament::tabs>
        <x-filament::tabs.item wire:click="$set('activeTab', 'permissions')" :active="$activeTab === 'permissions'" icon="heroicon-o-key">
            Permissions
        </x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'roles')" :active="$activeTab === 'roles'" icon="heroicon-o-shield-check">
            Assign Role
        </x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'endpoints')" :active="$activeTab === 'endpoints'" icon="heroicon-o-link">
            Endpoint Mapping
        </x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'resources')" :active="$activeTab === 'resources'" icon="heroicon-o-cube">
            Resource & Page Registry
        </x-filament::tabs.item>
        <x-filament::tabs.item wire:click="$set('activeTab', 'guide')" :active="$activeTab === 'guide'" icon="heroicon-o-book-open">
            Panduan
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-6">
        @if($activeTab === 'permissions')
            @livewire(\App\Livewire\RbacPermissionsTable::class, key('perms-table'))
        @elseif($activeTab === 'roles')
            @livewire(\App\Livewire\RoleManagement::class, key('role-mgmt'))
        @elseif($activeTab === 'endpoints')
            @livewire(\App\Livewire\RbacEndpointsTable::class, key('endpoints-table'))
        @elseif($activeTab === 'resources')
            @livewire(\App\Livewire\RbacPagePermissionsTable::class, key('resources-table'))
        @elseif($activeTab === 'guide')
            {{ $this->guideSchema() }}
        @endif
    </div>
</x-filament::page>
