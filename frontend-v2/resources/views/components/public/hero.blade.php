{{-- Hero section --}}
<section id="beranda" class="relative overflow-hidden">
    {{-- Geometric pattern background --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 text-primary">
        <x-public.geometric-pattern class="absolute inset-0 h-full w-full" :opacity="0.07" />
        <div class="absolute inset-0 bg-gradient-to-b from-background/40 via-background/60 to-background"></div>
    </div>

    {{-- Hero carousel (Visual ditampikan lebih dulu pada mobile) --}}
    <div class="relative h-[40vh] min-h-[320px] lg:absolute lg:inset-y-0 lg:right-0 lg:h-full lg:w-1/2"
         x-data='{
            activeSlide: 0,
            slides: @json(config("handayani-public.hero.images", [])),
            autoSlideInterval: null,
            startAutoSlide() {
                this.autoSlideInterval = setInterval(() => {
                    this.next();
                }, 5000);
            },
            stopAutoSlide() {
                clearInterval(this.autoSlideInterval);
            },
            next() {
                this.activeSlide = this.activeSlide === this.slides.length - 1 ? 0 : this.activeSlide + 1;
            },
            prev() {
                this.activeSlide = this.activeSlide === 0 ? this.slides.length - 1 : this.activeSlide - 1;
            }
         }'
         x-init="startAutoSlide()"
         @mouseenter="stopAutoSlide()"
         @mouseleave="startAutoSlide()"
    >
        <div class="relative h-full w-full overflow-hidden group">
            {{-- Soft gradient to blend with the left background on large screens --}}
            <div class="hidden lg:block absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-background to-transparent z-10 pointer-events-none"></div>

            {{-- Images --}}
            <template x-for="(slide, index) in slides" :key="index">
                <img
                    :src="slide"
                    alt="Visual sekolah Handayani"
                    class="absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out"
                    :class="activeSlide === index ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    loading="lazy"
                />
            </template>

            {{-- Arrows Navigation --}}
            <button
                @click="prev(); stopAutoSlide(); startAutoSlide()"
                class="absolute left-4 lg:left-8 top-1/2 z-20 -translate-y-1/2 grid size-10 place-items-center rounded-full bg-background/80 text-foreground shadow-sm opacity-0 backdrop-blur-sm transition-all hover:bg-background group-hover:opacity-100 focus:opacity-100"
                aria-label="Gambar sebelumnya"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <button
                @click="next(); stopAutoSlide(); startAutoSlide()"
                class="absolute right-4 lg:right-8 top-1/2 z-20 -translate-y-1/2 grid size-10 place-items-center rounded-full bg-background/80 text-foreground shadow-sm opacity-0 backdrop-blur-sm transition-all hover:bg-background group-hover:opacity-100 focus:opacity-100"
                aria-label="Gambar selanjutnya"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>

            {{-- Dots Pagination --}}
            <div class="absolute bottom-6 left-0 right-0 z-20 flex justify-center gap-2">
                <template x-for="(slide, index) in slides" :key="index">
                    <button
                        @click="activeSlide = index; stopAutoSlide(); startAutoSlide()"
                        :class="activeSlide === index ? 'bg-primary w-6' : 'bg-white/70 w-2 hover:bg-white'"
                        class="h-2 rounded-full transition-all duration-300 shadow-sm"
                        :aria-label="'Ke gambar ' + (index + 1)"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    <div class="relative mx-auto max-w-7xl lg:grid lg:grid-cols-2 lg:px-8">
        {{-- Text content --}}
        <div class="px-5 py-12 lg:py-32 lg:pr-8 lg:pl-0 z-10 flex flex-col justify-center">
            {{-- H1 --}}
            <h1 class="font-display text-4xl font-bold leading-[1.05] text-foreground md:text-5xl lg:text-6xl">
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
                @foreach(config('handayani-public.hero.stats', []) as $stat)
                    <div>
                        <dt class="font-display text-2xl font-bold text-foreground">{{ $stat['key'] }}</dt>
                        <dd class="mt-1 text-xs text-muted-foreground">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

</section>
