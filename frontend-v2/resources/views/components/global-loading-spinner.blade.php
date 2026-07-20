@props([
    // Comma-separated Livewire method/property names — same value you'd pass to
    // wire:target. Leave empty to react to ANY loading state of the enclosing
    // Livewire component (only do this on components with one primary action).
    'target' => null,
    'message' => 'Memuat data...',
    // true: always-visible markup — for Livewire #[Lazy] placeholder() returns,
    // where the "loading" state IS this markup until Livewire swaps in the real
    // component; there's no wire:loading event to hook since the real component
    // doesn't exist in the DOM yet. false (default): wire:loading-controlled
    // overlay for actions on an already-mounted component (filters, submits,
    // table pagination-style refreshes, ...).
    'static' => false,
])

{{--
    One spinner design reused everywhere a page/component fetches data without
    its own dedicated loading UI (Filament tables and components that already
    have a local wire:loading indicator don't need this — see CLAUDE.md).
--}}
@if($static)
    <div class="flex min-h-[50vh] w-full items-center justify-center" role="status" aria-live="polite" aria-label="{{ $message }}">
        <x-spinner-icon :message="$message" />
    </div>
@else
    <div
        @if($target) wire:loading.flex wire:target="{{ $target }}" @else wire:loading.flex @endif
        class="hidden fixed inset-0 z-[60] items-center justify-center bg-white/70 dark:bg-gray-950/70 backdrop-blur-sm"
        role="status"
        aria-live="polite"
        aria-label="{{ $message }}"
    >
        <x-spinner-icon :message="$message" />
    </div>
@endif
