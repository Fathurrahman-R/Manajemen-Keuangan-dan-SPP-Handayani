{{-- Ekstrakurikuler section --}}
<section id="ekstrakurikuler" class="border-y border-border bg-surface">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
        {{-- Section header --}}
        <x-public.reveal class="max-w-2xl">
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-accent">Ekstrakurikuler</span>
            <h2 class="mt-3 font-display text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                {{ config('handayani-public.ekstrakurikuler.title') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                {{ config('handayani-public.ekstrakurikuler.description') }}
            </p>
        </x-public.reveal>

        @php
            $kegiatan = config('handayani-public.ekstrakurikuler.kegiatan', []);
        @endphp

        {{-- Activity cards --}}
        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($kegiatan as $i => $item)
                <x-public.reveal delay="{{ $i * 100 }}ms">
                    <article class="group flex h-full flex-col rounded-2xl border border-border bg-background p-7 transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10">
                        <div class="flex items-center justify-between gap-3">
                            @if($item['sifat'] === 'Wajib')
                                <span class="inline-flex items-center rounded-full bg-gradient-to-br from-primary to-primary/80 px-3 py-1 text-xs font-bold tracking-wide text-primary-foreground">
                                    {{ $item['sifat'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-border px-3 py-1 text-xs font-semibold tracking-wide text-muted-foreground">
                                    {{ $item['sifat'] }}
                                </span>
                            @endif
                            <span class="text-xs font-medium text-muted-foreground">{{ $item['sasaran'] }}</span>
                        </div>
                        <h3 class="mt-5 font-display text-xl font-bold text-foreground">{{ $item['name'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ $item['desc'] }}</p>
                    </article>
                </x-public.reveal>
            @endforeach
        </div>
    </div>
</section>
