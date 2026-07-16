<div class="ms-4 me-2">
    <select 
        wire:model.live="activeBranchId"
        class="block w-48 text-sm font-medium border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-primary-500"
    >
        @if(empty($branches))
            <option value="">-- Tidak ada cabang --</option>
        @else
            @foreach($branches as $branch)
                <option value="{{ $branch['id'] }}">{{ $branch['location'] }}</option>
            @endforeach
        @endif
    </select>
</div>
