{{-- Jenjang section --}}
<section id="jenjang" class="bg-background">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
        {{-- Section header --}}
        <x-public.reveal class="max-w-2xl">
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-accent">Jenjang Pendidikan</span>
            <h2 class="mt-3 font-display text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                {{ config('handayani-public.jenjang.title') }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                {{ config('handayani-public.jenjang.description') }}
            </p>
        </x-public.reveal>

        @php
            $levels = config('handayani-public.jenjang.levels', []);
        @endphp

        {{-- Level cards --}}
        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($levels as $i => $level)
                <x-public.reveal delay="{{ $i * 100 }}ms">
                    <article class="group relative flex h-full flex-col rounded-2xl border border-border bg-surface p-7 transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center rounded-full bg-gradient-to-br from-primary to-primary/80 px-3 py-1 text-xs font-bold tracking-wide text-primary-foreground">
                                {{ $level['code'] }}
                            </span>
                            <span class="text-xs font-medium text-muted-foreground">{{ $level['age'] }}</span>
                        </div>
                        <h3 class="mt-5 font-display text-xl font-bold text-foreground">{{ $level['name'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ $level['desc'] }}</p>

                        <ul class="mt-6 space-y-2.5">
                            @foreach($level['programs'] as $program)
                                <li class="flex items-start gap-2.5 text-sm text-foreground">
                                    <span class="mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-accent/10 text-accent">
                                        {{-- Check icon --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span>
                                    {{ $program }}
                                </li>
                            @endforeach
                        </ul>

                        <a
                            href="#kontak"
                            class="mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-primary transition-colors hover:text-primary/80"
                        >
                            Info pendaftaran
                            {{-- ArrowUpRight icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                                <path d="M7 7h10v10"/>
                                <path d="M7 17 17 7"/>
                            </svg>
                        </a>
                    </article>
                </x-public.reveal>
            @endforeach
        </div>
    </div>
</section>
