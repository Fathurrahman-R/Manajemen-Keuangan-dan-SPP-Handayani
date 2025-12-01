<div class="bg-white rounded-lg p-4 flex flex-col gap-y-8 border border-gray-200">
    <div class="w-full p-4">
        <div class="w-full flex flex-col gap-y-4">
            <p class="text-4xl font-semibold">Detail Wali</p>
            <div class="w-full flex flex-row gap-x-4">
                <div class="w-full flex flex-col gap-y-4">
                    <!-- Nama -->
                    <div class="flex flex-col gap-y-2">
                        <span>Nama Lengkap</span>
                        <div class="p-2 border border-gray-300 rounded-md">
                            <p>{{ $item['nama'] }}</p>
                        </div>
                    </div>

                    <!-- Agama & Jenis Kelamin -->
                    <div class="w-full flex flex-row gap-x-4">
                        <div class="w-full flex flex-col gap-y-2">
                            <span>Agama</span>
                            <div class="p-2 border border-gray-300 rounded-md">
                                {{ $item['agama'] }}
                            </div>
                        </div>
                        <div class="w-full flex flex-col gap-y-2">
                            <span>Jenis Kelamin</span>
                            <div class="p-2 border border-gray-300 rounded-md">
                                {{ $item['jenis_kelamin'] }}
                            </div>
                        </div>
                    </div>

                    <!-- Pendidikan Terakhir & Pekerjaan -->
                    <div class="w-full flex flex-row gap-x-4">
                        <div class="w-full flex flex-col gap-y-2">
                            <span>Pendidikan Terakhir</span>
                            <div class="p-2 border border-gray-300 rounded-md">
                                {{ $item['pendidikan_terakhir'] }}
                            </div>
                        </div>
                        <div class="w-full flex flex-col gap-y-2">
                            <span>Pekerjaan</span>
                            <div class="p-2 border border-gray-300 rounded-md">
                                {{ $item['pekerjaan'] }}
                            </div>
                        </div>
                    </div>

                    <!-- No. HP -->
                    <div class="flex flex-col gap-y-2">
                        <span>No. HP</span>
                        <div class="p-2 border border-gray-300 rounded-md">
                            {{ $item['no_hp'] }}
                        </div>
                    </div>
                </div>
                <div class="w-full h-full flex flex-col gap-y-4">
                    <div class="w-full h-full flex flex-col gap-y-2">
                        <span>Alamat</span>
                        <div class="p-2 h-40 border border-gray-300 rounded-md">
                            {{ $item['alamat'] }}
                        </div>
                    </div>
                    <div class="w-full h-full flex flex-col gap-y-2">
                        <span>Keterangan</span>
                        <div class="p-2 h-24 border border-gray-300 rounded-md">
                            {{ $item['keterangan'] ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>