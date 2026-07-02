{{-- Hero section --}}
<section id="beranda" class="relative overflow-hidden">
    {{-- Geometric pattern background --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 text-primary">
        <x-public.geometric-pattern class="absolute inset-0 h-full w-full" :opacity="0.07" />
        <div class="absolute inset-0 bg-gradient-to-b from-background/40 via-background/60 to-background"></div>
    </div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:py-28">
        {{-- Text content --}}
        <div class="lg:col-span-7">
            {{-- Badge --}}
{{--            <span class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary">--}}
{{--                <span class="size-1.5 rounded-full bg-accent"></span>--}}
{{--                Lembaga Pendidikan Anak--}}
{{--            </span>--}}

            {{-- H1 --}}
            <h1 class="mt-5 font-display text-4xl font-bold leading-[1.05] text-foreground md:text-5xl lg:text-6xl">
                {{ config('handayani-public.name') }}
            </h1>

            {{-- Tagline --}}
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                {{ config('handayani-public.tagline') }}. Tiga jenjang pendidikan terpadu — KB/PAUD, TK, dan Madrasah Ibtidaiyah — dalam satu lingkungan yang aman dan berkualitas.
            </p>

            {{-- CTAs --}}
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a
                    href="#tentang"
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-5 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                >
                    Kenali Kami
                    {{-- ArrowRight icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                        <path d="M5 12h14"/>
                        <path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
                <a
                    href="{{ config('handayani-public.spp_portal_url') }}"
                    class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/20 transition-transform hover:-translate-y-0.5"
                >
                    {{-- ShieldCheck icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Portal Pembayaran SPP
                </a>
            </div>

            {{-- Stats --}}
            <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-border pt-6">
                @foreach([
                    ['key' => '3', 'value' => 'Jenjang Terpadu'],
                    ['key' => '20+', 'value' => 'Tahun Berdiri'],
                    ['key' => '100%', 'value' => 'Kurikulum Nasional'],
                ] as $stat)
                    <div>
                        <dt class="font-display text-2xl font-bold text-foreground">{{ $stat['key'] }}</dt>
                        <dd class="mt-1 text-xs text-muted-foreground">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Hero illustration --}}
        <div class="lg:col-span-5">
            <div class="relative">
                <div aria-hidden="true" class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-primary/10 to-accent/10 blur-2xl"></div>
                <div class="relative overflow-hidden rounded-3xl border border-border bg-surface shadow-xl shadow-primary/5">
                    <img
                        src="{{ asset('images/hero-illustration.jpg') }}"
                        alt="Ilustrasi gedung sekolah Handayani"
                        width="1024"
                        height="1024"
                        class="h-full w-full object-cover"
                    />
                </div>
            </div>
        </div>
    </div>
</section>
