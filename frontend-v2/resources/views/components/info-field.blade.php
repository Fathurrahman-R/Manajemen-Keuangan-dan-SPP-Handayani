@props(['label', 'value' => '-', 'class' => ''])

<div {{ $attributes->merge(['class' => $class]) }}>
    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
    <dd class="mt-0.5 text-sm text-gray-950 dark:text-white">{{ $value }}</dd>
</div>
