<div class="space-y-4">
    @php($errorRows = $preview['error_rows'] ?? 0)

    @if ($errorRows > 0)
        <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
            <p class="font-semibold">{{ $preview['summary'] ?? 'Ada baris yang tidak valid.' }}</p>
            <p class="mt-1">
                Tidak ada data yang disimpan. Perbaiki baris di bawah lewat tombol
                <span class="font-semibold">Perbaiki</span>, atau perbaiki file Excel-nya lalu upload ulang.
            </p>
        </div>

        {{ $this->table }}
    @else
        <div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
            Semua data valid. {{ $preview['total_rows'] ?? 0 }} baris siap diimport.
        </div>
    @endif

    <div class="flex justify-end">
        {{ $this->importSekarangAction }}
    </div>
</div>
