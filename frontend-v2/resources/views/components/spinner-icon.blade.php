@props(['message' => 'Memuat data...'])

{{--
    Shared visual (icon + label) for both wire:loading-driven spinners
    (global-loading-spinner) and Alpine-driven ones (branch-switcher, which
    needs to stay visible across a request+navigate cycle, not just one
    Livewire request — see branch-switcher.blade.php).
--}}
<div class="flex flex-col items-center gap-3">
    {{
        \Filament\Support\generate_loading_indicator_html(
            new \Illuminate\View\ComponentAttributeBag(['class' => 'text-gray-400 dark:text-gray-500']),
            \Filament\Support\Enums\IconSize::Large,
        )
    }}
    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $message }}</p>
</div>
