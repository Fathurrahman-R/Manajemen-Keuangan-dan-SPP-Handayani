<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'in:xlsx,csv'],
            'tahun_ajaran_id' => ['nullable', 'integer'],
            'jenjang' => ['nullable', 'in:TK,MI,KB'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'status' => ['nullable', 'in:Lunas,Belum Lunas,Belum Dibayar'],
        ];
    }
}
