<?php

namespace Tests\Feature;

use App\Exports\SiswaExport;
use App\Exports\SiswaImportTemplate;
use App\Models\Branch;
use App\Models\Siswa;
use Tests\TestCase;

class SiswaImportExportJenjangColumnsTest extends TestCase
{
    /**
     * Export/template columns must match what the create/edit siswa form actually
     * collects per jenjang: MI has NISN/asal_sekolah/kelas_diterima/tahun_diterima/
     * ayah/ibu, KB/TK has wali instead (see SiswaMIRequest vs SiswaKBRequest).
     */
    public function test_siswa_export_headings_are_scoped_to_jenjang(): void
    {
        $branch = Branch::factory()->create();
        $query = Siswa::query()->where('branch_id', $branch->id);

        $miHeadings = (new SiswaExport($query, null, 'MI'))->headings();
        $this->assertContains('NISN', $miHeadings);
        $this->assertContains('Nama Ayah', $miHeadings);
        $this->assertContains('Kelas Diterima', $miHeadings);
        $this->assertNotContains('Nama Wali', $miHeadings);

        $kbHeadings = (new SiswaExport($query, null, 'KB'))->headings();
        $this->assertContains('Nama Wali', $kbHeadings);
        $this->assertNotContains('NISN', $kbHeadings);
        $this->assertNotContains('Nama Ayah', $kbHeadings);
        $this->assertNotContains('Kelas Diterima', $kbHeadings);

        // No jenjang filter (mixed export) falls back to every column.
        $allHeadings = (new SiswaExport($query, null, null))->headings();
        $this->assertContains('NISN', $allHeadings);
        $this->assertContains('Nama Ayah', $allHeadings);
        $this->assertContains('Nama Wali', $allHeadings);
    }

    public function test_siswa_import_template_headings_and_sample_are_scoped_to_jenjang(): void
    {
        $branch = Branch::factory()->create();

        $miTemplate = new SiswaImportTemplate($branch->id, 'MI');
        $miHeadings = $miTemplate->headings();
        $this->assertContains('nisn', $miHeadings);
        $this->assertContains('nama_ayah', $miHeadings);
        $this->assertNotContains('nama_wali', $miHeadings);

        // Sample row's kelas_diterima must be a Roman numeral matching the
        // edit form's Select options (I-VI), not an Arabic numeral like "1".
        $miRow = $miTemplate->array()[0];
        $kelasDiterimaIndex = array_search('kelas_diterima', $miHeadings, true);
        $this->assertContains($miRow[$kelasDiterimaIndex], ['I', 'II', 'III', 'IV', 'V', 'VI']);

        $kbTemplate = new SiswaImportTemplate($branch->id, 'KB');
        $kbHeadings = $kbTemplate->headings();
        $this->assertContains('nama_wali', $kbHeadings);
        $this->assertNotContains('nisn', $kbHeadings);
        $this->assertNotContains('nama_ayah', $kbHeadings);
        $this->assertNotContains('kelas_diterima', $kbHeadings);
    }
}
