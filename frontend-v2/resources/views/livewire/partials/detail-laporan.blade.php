@php
    $tanggal = $tanggal ?? null;
    $bulan = $bulan ?? null;
    $tahun = $tahun ?? null;
    $key = $tanggal ?? ('rekap-' . $bulan . '-' . $tahun);
@endphp

<div class="space-y-6">
    <section>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Pemasukan</h3>
        <livewire:laporan-detail-pemasukan
            :tanggal="$tanggal"
            :bulan="$bulan"
            :tahun="$tahun"
            :key="'pemasukan-' . $key"
        />
    </section>

    <section>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Pengeluaran</h3>
        <livewire:laporan-detail-pengeluaran
            :tanggal="$tanggal"
            :bulan="$bulan"
            :tahun="$tahun"
            :key="'pengeluaran-' . $key"
        />
    </section>
</div>
