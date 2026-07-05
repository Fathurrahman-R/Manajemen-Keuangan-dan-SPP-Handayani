{{-- Footer --}}
<footer class="bg-background">
    {{-- Gradient top bar --}}
    <div class="h-1 w-full bg-gradient-to-r from-primary via-primary/70 to-accent"></div>

    <div class="mx-auto max-w-7xl px-5 py-14 lg:px-8">
        <div class="grid gap-10 md:grid-cols-2">
            {{-- Logo & tagline --}}
            <div>
                <div class="flex items-center gap-2.5">
                    @if(config('handayani-public.logo'))
                        <img src="{{ asset(config('handayani-public.logo')) }}" alt="Logo {{ config('handayani-public.short_name') }}" class="size-8 rounded-md object-cover">
                    @else
                        <span aria-hidden="true" class="grid size-8 place-items-center rounded-md bg-primary text-primary-foreground">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2 L20 7 L20 17 L12 22 L4 17 L4 7 Z" />
                                <path d="M12 7 L16 9.5 L16 14.5 L12 17 L8 14.5 L8 9.5 Z" />
                            </svg>
                        </span>
                    @endif
                    <span class="font-display text-base font-bold tracking-tight">{{ config('handayani-public.short_name') }}</span>
                </div>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-muted-foreground">
                    {{ config('handayani-public.name') }}. {{ config('handayani-public.tagline') }}.
                </p>
            </div>

            {{-- Links & address --}}
            <div class="md:text-right">
                <ul class="flex flex-wrap gap-x-6 gap-y-2 md:justify-end">
                    @foreach([
                        ['href' => '#tentang', 'label' => 'Tentang'],
                        ['href' => '#jenjang', 'label' => 'Jenjang'],
                        ['href' => '#spp', 'label' => 'Portal SPP'],
                        ['href' => '#kontak', 'label' => 'Kontak'],
                    ] as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="text-sm font-medium text-muted-foreground hover:text-foreground">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-6 text-xs text-muted-foreground">{{ config('handayani-public.address') }}</p>
            </div>
        </div>

        {{-- Copyright --}}
        <div class="mt-10 flex flex-col items-start justify-between gap-4 border-t border-border pt-6 md:flex-row md:items-center">
            <p class="text-xs text-muted-foreground">
                &copy; {{ date('Y') }} {{ config('handayani-public.name') }}. Semua hak dilindungi.
            </p>
            <p class="text-xs text-muted-foreground">Dibangun dengan amanah.</p>
        </div>
    </div>
</footer>
