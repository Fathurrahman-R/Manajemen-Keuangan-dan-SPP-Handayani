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
                        Portal Pembayaran SPP
                    </h2>
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-primary-foreground/85">
                        Lakukan pembayaran SPP kapan saja melalui portal resmi Yayasan Handayani. Riwayat transparan, konfirmasi otomatis, dan terlindungi enkripsi end-to-end.
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
                        $badges = [
                            [
                                'label' => 'Terverifikasi',
                                'desc' => 'Akun siswa diverifikasi langsung oleh admin yayasan.',
                                'icon' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
                            ],
                            [
                                'label' => 'Real-time',
                                'desc' => 'Status pembayaran tersinkron seketika.',
                                'icon' => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
                            ],
                            [
                                'label' => 'Aman SSL',
                                'desc' => 'Enkripsi industri standar untuk setiap transaksi.',
                                'icon' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
                            ],
                        ];
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
