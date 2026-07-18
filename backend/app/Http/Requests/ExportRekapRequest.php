<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportRekapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'in:xlsx,csv'],
            'tahun' => ['required', 'integer', 'digits:4'],
        ];
    }
}
