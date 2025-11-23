<div class="bg-white rounded-lg p-4 flex flex-col gap-y-4 border border-gray-200">
    <div class="flex flex-row justify-between items-center">
        <div class="tabview">
            <ul class="nav nav-tab flex flex-row gap-x-2">
                <li class="nav-link {{ $activeTab != 'TK' ? 'cursor-pointer' : null }}" wire:click="setActiveTab('TK')">TK</li>
                <li class="nav-link {{ $activeTab != 'KB' ? 'cursor-pointer' : null }}" wire:click="setActiveTab('KB')">KB</li>
                <li class="nav-link {{ $activeTab != 'MI' ? 'cursor-pointer' : null }}" wire:click="setActiveTab('MI')">MI</li>
            </ul>
        </div>
        <div class="flex flex-row gap-x-2">
            <div class="flex flex-row justify-between gap-x-2 items-center bg-white rounded-lg border-2 border-gray-200 py-0.5 ps-2 pe-2">
                <input class="border-none focus:outline-none ring-0 focus:ring-0 w-full" type="text" placeholder="Cari...">
                <x-icon name="magnifying-glass" class="w-6 h-6" />
            </div>
            <button wire:click="$dispatch('openModal', { component: 'create-user' })" class="btn py-2 px-4 rounded-lg bg-blue-500 text-white font-semibold">Tambah</button>
        </div>
    </div>

    <div class="w-full">
        <table class="w-full">
            <thead class="bg-gray-300">
                <tr>
                    <th class="py-2 rounded-tl-lg">Nama</th>
                    <th class="py-2 rounded-tr-lg">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                <tr class="py-4 border-l border-r border-b border-gray-200">
                    <td class="text-center py-4">{{ $item['nama'] }}</td>
                    <td>
                        <div class="flex flex-row gap-x-2 justify-center">
                            <x-icon name="pencil" class="w-6 h-6 text-[#E9D502] cursor-pointer" />
                            <x-icon name="trash" class="w-6 h-6 text-[#FF0D0D] cursor-pointer" wire:click="$dispatch('openModal', { component: 'create-user' })" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="py-4 border-l border-r border-b border-gray-200">
                    <td colspan="2" class="py-4 text-center">No Data</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>