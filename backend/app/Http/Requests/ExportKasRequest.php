<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportKasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'in:xlsx,csv'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'digits:4'],
        ];
    }
}
