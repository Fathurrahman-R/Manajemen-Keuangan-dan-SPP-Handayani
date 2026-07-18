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
     */
    public function generateSiswaTemplate(int $branchId): BinaryFileResponse
    {
        return Excel::download(
            new SiswaImportTemplate($branchId),
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
