{{-- Sticky navigation header --}}
<header
    x-data="{
        open: false,
        closeMenu() { this.open = false },
        init() {
            this.$watch('open', v => {
                document.body.style.overflow = v ? 'hidden' : '';
            });
        }
    }"
    class="sticky top-0 z-50 border-b border-border/60 bg-background/85 backdrop-blur"
>
    <nav aria-label="Navigasi utama" class="mx-auto flex h-16 max-w-7xl items-center justify-between px-5 lg:px-8">
        {{-- Logo --}}
        <a href="#beranda" @click.prevent="document.querySelector('#beranda').scrollIntoView({ behavior: 'smooth' })" class="flex items-center gap-2.5">
            @if(config('handayani-public.logo'))
                <img src="{{ asset(config('handayani-public.logo')) }}" alt="Logo {{ config('handayani-public.short_name') }}" class="size-8 rounded-md object-cover">
            @else
                <span aria-hidden="true" class="grid size-8 place-items-center rounded-md text-primary-foreground bg-primary">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2 L20 7 L20 17 L12 22 L4 17 L4 7 Z" />
                        <path d="M12 7 L16 9.5 L16 14.5 L12 17 L8 14.5 L8 9.5 Z" />
                    </svg>
                </span>
            @endif
            <span class="font-display text-base font-bold tracking-tight">{{ config('handayani-public.short_name') }}</span>
        </a>

        {{-- Desktop navigation --}}
        <ul class="hidden items-center gap-8 md:flex">
            @foreach([
                ['href' => '#beranda', 'label' => 'Beranda'],
                ['href' => '#tentang', 'label' => 'Tentang'],
                ['href' => '#jenjang', 'label' => 'Jenjang'],
                ['href' => '#spp', 'label' => 'SPP'],
                ['href' => '#kontak', 'label' => 'Kontak'],
            ] as $link)
                <li>
                    <a 
                        href="{{ $link['href'] }}" 
                        @click.prevent="document.querySelector('{{ $link['href'] }}').scrollIntoView({ behavior: 'smooth' })"
                        class="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Desktop Portal SPP button --}}
        <div class="hidden md:block">
            <a
                href="{{ config('handayani-public.spp_portal_url') }}"
                class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-transform hover:-translate-y-0.5"
            >
                {{-- ShieldCheck icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                Portal SPP
            </a>
        </div>

        {{-- Mobile hamburger button --}}
        <button
            type="button"
            @click="open = !open"
            @keydown.escape="open = false"
            :aria-label="open ? 'Tutup menu' : 'Buka menu'"
            :aria-expanded="open"
            class="grid size-10 place-items-center rounded-md border border-border md:hidden"
        >
            {{-- Menu icon --}}
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                <line x1="4" x2="20" y1="12" y2="12"/>
                <line x1="4" x2="20" y1="6" y2="6"/>
                <line x1="4" x2="20" y1="18" y2="18"/>
            </svg>
            {{-- X icon --}}
            <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                <path d="M18 6 6 18"/>
                <path d="m6 6 12 12"/>
            </svg>
        </button>
    </nav>

    {{-- Mobile dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        @click.outside="closeMenu()"
        @keydown.escape.window="closeMenu()"
        x-cloak
        class="border-t border-border md:hidden"
    >
        <ul class="mx-auto flex max-w-7xl flex-col gap-1 px-5 py-3">
            @foreach([
                ['href' => '#beranda', 'label' => 'Beranda'],
                ['href' => '#tentang', 'label' => 'Tentang'],
                ['href' => '#jenjang', 'label' => 'Jenjang'],
                ['href' => '#spp', 'label' => 'SPP'],
                ['href' => '#kontak', 'label' => 'Kontak'],
            ] as $link)
                <li>
                    <a
                        href="{{ $link['href'] }}"
                        @click.prevent="document.querySelector('{{ $link['href'] }}').scrollIntoView({ behavior: 'smooth' }); closeMenu()"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-muted"
                    >
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
            <li>
                <a
                    href="{{ config('handayani-public.spp_portal_url') }}"
                    @click="closeMenu()"
                    class="mt-1 inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Portal SPP
                </a>
            </li>
        </ul>
    </div>
</header>
