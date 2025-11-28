<div class="bg-white rounded-lg p-4 flex flex-col gap-y-4 border border-gray-200">
    <div class="flex flex-row justify-between items-center">
        <div class="tabview">
            <x-filament::tabs label="Content tabs">
                <x-filament::tabs.item
                    :active="$activeTab === 'TK'"
                    wire:click="$set('activeTab', 'TK')">
                    TK
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'KB'"
                    wire:click="$set('activeTab', 'KB')">
                    KB
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'MI'"
                    wire:click="$set('activeTab', 'MI')">
                    MI
                </x-filament::tabs.item>
            </x-filament::tabs>
            <!-- <ul class="nav nav-tab flex flex-row gap-x-2">
                <li class="nav-link {{ $activeTab != 'TK' ? 'cursor-pointer' : 'border-b-2 border-blue-300 transition-colors duration-100' }} px-4" wire:click="setActiveTab('TK')">TK</li>
                <li class="nav-link {{ $activeTab != 'KB' ? 'cursor-pointer' : 'border-b-2 border-blue-300 transition-colors duration-100' }} px-4" wire:click="setActiveTab('KB')">KB</li>
                <li class="nav-link {{ $activeTab != 'MI' ? 'cursor-pointer' : 'border-b-2 border-blue-300 transition-colors duration-100' }} px-4" wire:click="setActiveTab('MI')">MI</li>
            </ul> -->
        </div>
    </div>

    <div class="w-full">
        {{ $this->table }}
    </div>
</div>