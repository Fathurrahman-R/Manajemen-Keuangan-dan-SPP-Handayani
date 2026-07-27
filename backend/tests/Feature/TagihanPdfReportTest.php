<?php

namespace Tests\Feature;

use Tests\TestCase;

class TagihanPdfReportTest extends TestCase
{
    /**
     * Each student group must live in its own table with page-break-inside:avoid,
     * so DomPDF moves the whole rowspan'd group to a new page instead of
     * splitting it (which would otherwise strand the student-info cells on
     * the previous page and shift the continuation row's values left).
     */
    public function test_each_student_group_is_an_unbreakable_table_with_rowspan_intact(): void
    {
        $groupedRows = [
            [
                'nama' => 'Miss Dayana Cummings',
                'nis' => '000006',
                'jenjang' => 'MI',
                'kelas' => 'Kelas 1',
                'tagihans' => [
                    ['kode_tagihan' => 'TAG-2607-0017', 'jenis_tagihan' => 'SPP 2 Bulan Lagi', 'jatuh_tempo' => '21/09/2026', 'status' => 'Belum Dibayar', 'jumlah' => 150000, 'tmp' => 0, 'sisa' => 150000],
                    ['kode_tagihan' => 'TAG-2607-0018', 'jenis_tagihan' => 'Seragam', 'jatuh_tempo' => '20/06/2026', 'status' => 'Belum Dibayar', 'jumlah' => 350000, 'tmp' => 0, 'sisa' => 350000],
                    ['kode_tagihan' => 'TAG-2607-0020', 'jenis_tagihan' => 'SPP 4 Bulan Lagi', 'jatuh_tempo' => '20/11/2026', 'status' => 'Lunas', 'jumlah' => 150000, 'tmp' => 150000, 'sisa' => 0],
                ],
                'total_jumlah' => 650000,
                'total_terbayar' => 150000,
                'total_sisa' => 500000,
            ],
            [
                'nama' => 'Kellen Watsica IV',
                'nis' => '000007',
                'jenjang' => 'MI',
                'kelas' => 'Kelas 2',
                'tagihans' => [
                    ['kode_tagihan' => 'TAG-2607-0021', 'jenis_tagihan' => 'SPP', 'jatuh_tempo' => '21/09/2026', 'status' => 'Lunas', 'jumlah' => 150000, 'tmp' => 150000, 'sisa' => 0],
                ],
                'total_jumlah' => 150000,
                'total_terbayar' => 150000,
                'total_sisa' => 0,
            ],
        ];

        $html = view('Laporan.tagihan-pdf', [
            'groupedRows' => $groupedRows,
            'branchName' => 'Cabang Test',
            'periode' => '2026/2027',
            'jenjang' => null,
            'statusFilter' => [],
        ])->render();

        // CSS rule that keeps a group from splitting across a page.
        $this->assertMatchesRegularExpression(
            '/table\.siswa-group\s*\{[^}]*page-break-inside:\s*avoid/',
            $html
        );

        // One <table class="siswa-group"> per student, each carrying its own header.
        $this->assertSame(2, substr_count($html, 'class="siswa-group"'));
        $this->assertSame(2, substr_count($html, '<thead>'));

        // First student has 3 tagihan rows, so its 5 student-info cells span 3 rows.
        $this->assertSame(5, substr_count($html, 'rowspan="3"'));
        // Second student has a single row, so its 5 student-info cells span 1 row.
        $this->assertSame(5, substr_count($html, 'rowspan="1"'));
    }
}
