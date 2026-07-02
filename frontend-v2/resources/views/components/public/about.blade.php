{{-- About section --}}
<section id="tentang" class="border-y border-border bg-surface">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
        {{-- Section header --}}
        <x-public.reveal class="max-w-3xl">
            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-accent">Tentang Kami</span>
            <h2 class="mt-3 font-display text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                Lembaga pendidikan Islam yang fokus pada ilmu, adab, dan masa depan.
            </h2>
        </x-public.reveal>

        {{-- Misi / Visi cards --}}
        <div class="mt-14 grid gap-10 lg:grid-cols-2 lg:gap-16">
            <x-public.reveal class="rounded-2xl border border-border bg-background p-7">
                <h3 class="font-display text-sm font-semibold uppercase tracking-widest text-primary">Misi</h3>
                <p class="mt-3 text-base leading-relaxed text-foreground">
                    Menyelenggarakan pendidikan terpadu berbasis nilai Islam, kurikulum nasional, dan pengembangan karakter — untuk membentuk peserta didik yang berilmu, mandiri, dan berakhlak mulia.
                </p>
            </x-public.reveal>
            <x-public.reveal delay="80ms" class="rounded-2xl border border-border bg-background p-7">
                <h3 class="font-display text-sm font-semibold uppercase tracking-widest text-accent">Visi</h3>
                <p class="mt-3 text-base leading-relaxed text-foreground">
                    Menjadi yayasan pendidikan Islam rujukan yang melahirkan generasi cerdas spiritual, intelektual, dan sosial — siap berkontribusi bagi umat dan bangsa.
                </p>
            </x-public.reveal>
        </div>

        {{-- Nilai Institusional --}}
        <div class="mt-16">
            <x-public.reveal>
                <h3 class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                    Nilai Institusional
                </h3>
            </x-public.reveal>

            @php
                $values = [
                    ['n' => '01', 'title' => 'Integritas', 'desc' => 'Kejujuran, amanah, dan tanggung jawab menjadi pondasi setiap pendidik dan peserta didik.'],
                    ['n' => '02', 'title' => 'Profesionalisme', 'desc' => 'Tenaga pendidik kompeten dengan kurikulum nasional terstandar dan pengembangan berkelanjutan.'],
                    ['n' => '03', 'title' => 'Keteladanan', 'desc' => "Mengutamakan akhlak Qur'ani dalam keseharian, dari guru ke murid, dari sekolah ke keluarga."],
                ];
            @endphp

            <ul class="mt-6 divide-y divide-border border-y border-border">
                @foreach($values as $i => $v)
                    <x-public.reveal as="li" delay="{{ $i * 80 }}ms" class="grid items-baseline gap-4 py-7 md:grid-cols-12">
                        <span class="font-display text-2xl font-bold text-primary md:col-span-1">{{ $v['n'] }}</span>
                        <h4 class="font-display text-xl font-semibold text-foreground md:col-span-3">{{ $v['title'] }}</h4>
                        <p class="text-base leading-relaxed text-muted-foreground md:col-span-8">{{ $v['desc'] }}</p>
                    </x-public.reveal>
                @endforeach
            </ul>
        </div>
    </div>
</section>
