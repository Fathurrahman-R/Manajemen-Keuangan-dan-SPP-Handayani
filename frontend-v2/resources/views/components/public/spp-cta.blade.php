{{-- SPP CTA section --}}
<section id="spp" class="relative overflow-hidden">
    <div class="relative bg-gradient-to-br from-primary via-primary to-accent text-primary-foreground">
        {{-- Geometric pattern overlay --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 text-primary-foreground">
            <x-public.geometric-pattern class="absolute inset-0 h-full w-full" :opacity="0.12" stroke="currentColor" />
            <div class="absolute inset-x-0 top-0 h-px bg-primary-foreground/30"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-12">
                {{-- Text content --}}
                <x-public.reveal class="lg:col-span-7">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-foreground/80">
                        Layanan Orang Tua
                    </span>
                    <h2 class="mt-3 font-display text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                        {{ config('handayani-public.spp_cta.title') }}
                    </h2>
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-primary-foreground/85">
                        {{ config('handayani-public.spp_cta.description') }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ config('handayani-public.spp_portal_url') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-background px-6 py-3.5 text-sm font-bold text-primary shadow-lg transition-transform hover:-translate-y-0.5"
                        >
                            Masuk Portal SPP
                            {{-- ArrowRight icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                <path d="M5 12h14"/>
                                <path d="m12 5 7 7-7 7"/>
                            </svg>
                        </a>
                        <a
                            href="#kontak"
                            class="inline-flex items-center gap-2 rounded-full border border-primary-foreground/30 px-5 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary-foreground/10"
                        >
                            Butuh bantuan?
                        </a>
                    </div>
                </x-public.reveal>

                {{-- Trust badges --}}
                <x-public.reveal delay="120ms" class="lg:col-span-5">
                    @php
                        $badges = config('handayani-public.spp_cta.badges', []);
                    @endphp

                    <ul class="grid gap-3">
                        @foreach($badges as $badge)
                            <li class="flex items-center gap-4 rounded-2xl border border-primary-foreground/15 bg-primary-foreground/5 px-5 py-4 backdrop-blur-sm">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-foreground/15">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                        {!! $badge['icon'] !!}
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-display text-base font-semibold">{{ $badge['label'] }}</p>
                                    <p class="text-xs text-primary-foreground/70">{{ $badge['desc'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-public.reveal>
            </div>
        </div>
    </div>
</section>
