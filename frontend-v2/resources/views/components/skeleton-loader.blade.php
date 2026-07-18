@props([
    'mode' => 'table', // table, card, or form
    'rows' => 5,
    'columns' => 4,
])

@if($mode === 'table')
    <div class="space-y-3 p-4" aria-label="Memuat data..." role="status">
        {{-- Table header skeleton --}}
        <div class="flex gap-4">
            @for($i = 0; $i < $columns; $i++)
                <div class="skeleton-text flex-1"></div>
            @endfor
        </div>

        {{-- Table rows skeleton --}}
        @for($r = 0; $r < $rows; $r++)
            <div class="flex gap-4 pt-2">
                @for($i = 0; $i < $columns; $i++)
                    <div class="skeleton h-3 flex-1 {{ $i === 0 ? 'w-1/3' : '' }}"></div>
                @endfor
            </div>
        @endfor
    </div>
@elseif($mode === 'card')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 p-4" aria-label="Memuat data..." role="status">
        @for($r = 0; $r < $rows; $r++)
            <div class="skeleton-card"></div>
        @endfor
    </div>
@elseif($mode === 'form')
    <div class="space-y-4 max-w-xl p-4" aria-label="Memuat formulir..." role="status">
        @for($r = 0; $r < $rows; $r++)
            <div class="space-y-2">
                <div class="skeleton h-3 w-1/4"></div>
                <div class="skeleton h-10 w-full rounded-lg"></div>
            </div>
        @endfor
    </div>
@endif
