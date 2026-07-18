<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'in:xlsx,csv'],
            'jenjang' => ['nullable', 'in:TK,MI,KB'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'status' => ['nullable', 'in:Aktif,Lulus,Pindah,Keluar'],
            'tahun_ajaran_id' => ['nullable', 'integer'],
        ];
    }
}
