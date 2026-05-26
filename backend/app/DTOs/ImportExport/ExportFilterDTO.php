<?php

namespace App\DTOs\ImportExport;

class ExportFilterDTO
{
    /**
     * @param string|null $jenjang Filter jenjang (TK, MI, KB)
     * @param int|null $kelasId Filter kelas ID
     * @param string|null $status Filter status
     * @param int|null $tahunAjaranId Filter tahun ajaran ID
     * @param string|null $tanggalMulai Filter tanggal mulai (Y-m-d)
     * @param string|null $tanggalSelesai Filter tanggal selesai (Y-m-d)
     * @param int|null $bulan Filter bulan (1-12)
     * @param int|null $tahun Filter tahun (e.g. 2024)
     * @param string $format Format output: 'xlsx' atau 'csv'
     */
    public function __construct(
        public readonly ?string $jenjang = null,
        public readonly ?int $kelasId = null,
        public readonly ?string $status = null,
        public readonly ?int $tahunAjaranId = null,
        public readonly ?string $tanggalMulai = null,
        public readonly ?string $tanggalSelesai = null,
        public readonly ?int $bulan = null,
        public readonly ?int $tahun = null,
        public readonly string $format = 'xlsx',
    ) {}

    /**
     * Create from request array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            jenjang: $data['jenjang'] ?? null,
            kelasId: isset($data['kelas_id']) ? (int) $data['kelas_id'] : null,
            status: $data['status'] ?? null,
            tahunAjaranId: isset($data['tahun_ajaran_id']) ? (int) $data['tahun_ajaran_id'] : null,
            tanggalMulai: $data['tanggal_mulai'] ?? null,
            tanggalSelesai: $data['tanggal_selesai'] ?? null,
            bulan: isset($data['bulan']) ? (int) $data['bulan'] : null,
            tahun: isset($data['tahun']) ? (int) $data['tahun'] : null,
            format: $data['format'] ?? 'xlsx',
        );
    }

    /**
     * Convert to array (useful for passing to services/queries).
     */
    public function toArray(): array
    {
        return array_filter([
            'jenjang' => $this->jenjang,
            'kelas_id' => $this->kelasId,
            'status' => $this->status,
            'tahun_ajaran_id' => $this->tahunAjaranId,
            'tanggal_mulai' => $this->tanggalMulai,
            'tanggal_selesai' => $this->tanggalSelesai,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'format' => $this->format,
        ], fn ($value) => $value !== null);
    }
}
