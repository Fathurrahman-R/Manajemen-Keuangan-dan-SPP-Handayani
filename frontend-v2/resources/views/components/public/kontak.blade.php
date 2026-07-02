{{-- Contact section --}}
<section id="kontak" class="border-t border-border bg-surface">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
        <div class="grid gap-12 lg:grid-cols-12">
            {{-- Contact info --}}
            <x-public.reveal class="lg:col-span-5">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-accent">Kontak</span>
                <h2 class="mt-3 font-display text-3xl font-bold leading-tight md:text-4xl">
                    Kunjungi atau hubungi kami.
                </h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    Admin Yayasan siap membantu pertanyaan seputar pendaftaran, kurikulum, dan layanan SPP setiap hari kerja, pukul 07.30 – 15.30 WIB.
                </p>

                <ul class="mt-8 space-y-5">
                    {{-- Address --}}
                    <li class="flex items-start gap-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Alamat</p>
                            <p class="mt-1 text-base text-foreground">{{ config('handayani-public.address') }}</p>
                        </div>
                    </li>

                    {{-- Phone --}}
                    <li class="flex items-start gap-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Telepon</p>
                            <a href="tel:{{ config('handayani-public.phone') }}" class="mt-1 block text-base text-foreground hover:text-primary">{{ config('handayani-public.phone') }}</a>
                        </div>
                    </li>

                    {{-- Email --}}
                    <li class="flex items-start gap-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                <rect width="20" height="16" x="2" y="4" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Email</p>
                            <a href="mailto:{{ config('handayani-public.email') }}" class="mt-1 block text-base text-foreground hover:text-primary">{{ config('handayani-public.email') }}</a>
                        </div>
                    </li>
                </ul>

                {{-- WhatsApp button --}}
                @php
                    $waLink = 'https://wa.me/' . config('handayani-public.whatsapp_number') . '?text=' . urlencode('Assalamualaikum, saya ingin bertanya tentang pendaftaran di Yayasan Handayani.');
                @endphp
                <a
                    href="{{ $waLink }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-8 inline-flex items-center gap-2.5 rounded-full bg-accent px-6 py-3.5 text-sm font-bold text-accent-foreground shadow-lg shadow-accent/20 transition-transform hover:-translate-y-0.5"
                >
                    {{-- MessageCircle icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                        <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                    </svg>
                    Chat via WhatsApp
                </a>
            </x-public.reveal>

            {{-- Map --}}
            <x-public.reveal delay="120ms" class="lg:col-span-7">
                <div class="aspect-[4/3] w-full overflow-hidden rounded-2xl border border-border bg-background">
                    <iframe
                        title="Peta Lokasi Handayani"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=106.7%2C-6.27%2C106.85%2C-6.2&layer=mapnik"
                        class="h-full w-full"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
            </x-public.reveal>
        </div>
    </div>
</section>
