<?php

namespace App\Services\ImportExport;

use App\Exports\SiswaImportTemplate;
use App\Exports\TagihanImportTemplate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateService
{
    /**
     * Generate and return the siswa import template.
     * Columns are scoped to $jenjang (MI vs KB/TK) when provided, matching
     * the fields actually collected by the create/edit siswa form per jenjang.
     */
    public function generateSiswaTemplate(int $branchId, ?string $jenjang = null): BinaryFileResponse
    {
        return Excel::download(
            new SiswaImportTemplate($branchId, $jenjang),
            'template_import_siswa.xlsx'
        );
    }

    /**
     * Generate and return the tagihan import template.
     */
    public function generateTagihanTemplate(int $branchId): BinaryFileResponse
    {
        return Excel::download(
            new TagihanImportTemplate($branchId),
            'template_import_tagihan.xlsx'
        );
    }
}
