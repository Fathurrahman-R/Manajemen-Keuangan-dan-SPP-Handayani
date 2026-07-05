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
                {{-- Gunakan isolate class agar z-index Leaflet terkurung di elemen ini dan tidak menutupi navbar --}}
                <div id="leaflet-map" class="aspect-[4/3] w-full overflow-hidden rounded-2xl border border-border bg-background isolate relative z-0">
                </div>
            </x-public.reveal>
        </div>
    </div>
</section>

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var mapSettings = @json(config('handayani-public.map_settings', []));
            
            var map = L.map('leaflet-map', {
                zoomControl: mapSettings.zoom_control ?? true,
                scrollWheelZoom: mapSettings.scroll_wheel_zoom ?? false
            });
            
            L.tileLayer(mapSettings.tile_url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: mapSettings.max_zoom || 19,
                attribution: mapSettings.attribution || '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            var branches = @json(config('handayani-public.branches', []));
            var padding = mapSettings.padding_fitbounds || 50;
            var defaultZoom = mapSettings.default_zoom || 13;
            
            if (branches.length > 0) {
                var bounds = [];
                branches.forEach(function(branch) {
                    if (branch.lat && branch.lng) {
                        var marker = L.marker([branch.lat, branch.lng]).addTo(map);
                        marker.bindPopup("<b>" + branch.name + "</b><br>" + branch.address);
                        bounds.push([branch.lat, branch.lng]);
                    }
                });
                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [padding, padding] });
                } else {
                    map.setView([-6.200000, 106.816666], defaultZoom);
                }
            } else {
                map.setView([-6.200000, 106.816666], defaultZoom);
            }
        });
    </script>
@endpush
