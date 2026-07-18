<div>
    {{-- Filter bar --}}
    <div class="mb-4 flex flex-wrap items-end gap-4">
        <div>
            <label for="filterType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Notifikasi</label>
            <select
                id="filterType"
                wire:model.live="filterType"
                class="block w-48 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
            >
                <option value="">Semua Tipe</option>
                <option value="tagihan_baru">Tagihan Baru</option>
                <option value="kwitansi">Kwitansi</option>
                <option value="reminder">Pengingat</option>
                <option value="overdue">Jatuh Tempo</option>
            </select>
        </div>
        <div>
            <label for="filterStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select
                id="filterStatus"
                wire:model.live="filterStatus"
                class="block w-48 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
            >
                <option value="">Semua Status</option>
                <option value="sent">Terkirim</option>
                <option value="failed">Gagal</option>
                <option value="skipped">Dilewati</option>
            </select>
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}
</div>
