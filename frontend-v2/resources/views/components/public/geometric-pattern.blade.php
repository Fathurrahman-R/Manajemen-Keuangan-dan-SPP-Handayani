@props([
    'opacity' => 0.04,
    'stroke' => 'currentColor',
    'class' => '',
])

<svg aria-hidden="true" {{ $attributes->merge(['class' => $class]) }} width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <pattern id="mashrabiya" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse">
            <g fill="none" stroke="{{ $stroke }}" stroke-width="1" opacity="{{ $opacity }}">
                {{-- 8-point star --}}
                <path d="M40 4 L48 24 L68 24 L52 38 L60 60 L40 46 L20 60 L28 38 L12 24 L32 24 Z" />
                {{-- outer octagon --}}
                <path d="M40 0 L68 12 L80 40 L68 68 L40 80 L12 68 L0 40 L12 12 Z" />
                {{-- inner square rotated --}}
                <path d="M40 16 L64 40 L40 64 L16 40 Z" />
            </g>
        </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#mashrabiya)" />
</svg>
