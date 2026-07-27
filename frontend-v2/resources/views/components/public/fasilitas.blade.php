{{-- Fasilitas section --}}
<section id="fasilitas" class="bg-background">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
        {{-- Section header --}}
        <x-public.reveal class="max-w-2xl">
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-accent">Sarana & Prasarana</span>
            <h2 class="mt-3 font-display text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                {{ config('handayani-public.fasilitas.title') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                {{ config('handayani-public.fasilitas.description') }}
            </p>
        </x-public.reveal>

        @php
            $sarana = config('handayani-public.fasilitas.sarana', []);
            $ruangPenunjang = config('handayani-public.fasilitas.ruang_penunjang', []);
        @endphp

        {{-- Sarana counters --}}
        <dl class="mt-14 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-border bg-border md:grid-cols-3">
            @foreach($sarana as $i => $item)
                <x-public.reveal delay="{{ $i * 70 }}ms" class="bg-surface p-7">
                    <dt class="font-display text-4xl font-bold text-primary">{{ $item['jumlah'] }}</dt>
                    <dd class="mt-2 text-sm font-medium text-muted-foreground">{{ $item['label'] }}</dd>
                </x-public.reveal>
            @endforeach
        </dl>

        {{-- Ruang penunjang --}}
        <div class="mt-12">
            <x-public.reveal>
                <h3 class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                    Ruang Penunjang
                </h3>
            </x-public.reveal>

            <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($ruangPenunjang as $i => $ruang)
                    <x-public.reveal as="li" delay="{{ $i * 60 }}ms" class="flex items-start gap-2.5 text-sm text-foreground">
                        <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-accent/10 text-accent">
                            {{-- Check icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </span>
                        {{ $ruang }}
                    </x-public.reveal>
                @endforeach
            </ul>
        </div>
    </div>
</section>
