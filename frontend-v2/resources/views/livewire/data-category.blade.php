<div class="bg-white rounded-lg p-4 flex flex-col gap-y-4 border border-gray-200">
    <div class="flex flex-row justify-between">
        <div class="flex flex-row justify-between gap-x-2 items-center bg-white rounded-lg border-2 border-gray-200 py-0.5 ps-2 pe-2">
            <input class="border-none focus:outline-none ring-0 focus:ring-0 w-full" type="text" placeholder="Cari...">
            <x-icon name="magnifying-glass" class="w-6 h-6" />
        </div>
        <button wire:click="$dispatch('openModal', { component: 'create-user' })" class="btn py-2 px-4 rounded-lg bg-blue-500 text-white font-semibold">Tambah</button>
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
                @foreach ($items as $item)
                <tr class="py-4 border-l border-r border-b border-gray-200">
                    <td class="text-center py-4">{{ $item['nama'] }}</td>
                    <td>
                        <div class="flex flex-row gap-x-2 justify-center">
                            <x-icon name="pencil" class="w-6 h-6 text-[#E9D502] cursor-pointer" />
                            <x-icon name="trash" class="w-6 h-6 text-[#FF0D0D] cursor-pointer" wire:click="$dispatch('openModal', { component: 'create-user' })" />
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>