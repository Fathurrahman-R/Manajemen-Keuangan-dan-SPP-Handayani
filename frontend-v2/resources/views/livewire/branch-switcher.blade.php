<div
    class="ms-4 me-2"
    x-data="{ switching: false }"
    x-on:livewire:navigated.window="switching = false"
>
    {{--
        Alpine-driven, not wire:loading — wire:loading only covers the AJAX
        call that updates activeBranchId; it hides the instant that response
        lands, before the Livewire navigate redirect (updatedActiveBranchId())
        finishes swapping the page, leaving a gap where Livewire's own
        nprogress bar took over (suppressed globally, see theme.css) with no
        spinner. `switching` stays true until `livewire:navigated` fires —
        NOT until the DOM gets replaced: Livewire's navigate morphs the body
        in place rather than tearing it down, so this component (and its
        Alpine state) survives the transition instead of resetting on its
        own, and the spinner would otherwise spin forever.
    --}}
    <div
        x-show="switching"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-white/70 dark:bg-gray-950/70 backdrop-blur-sm"
        role="status"
        aria-live="polite"
        aria-label="Memuat data cabang..."
    >
        <x-spinner-icon message="Memuat data cabang..." />
    </div>

    <select
        wire:model.live="activeBranchId"
        x-on:change="switching = true"
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
