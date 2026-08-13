<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPatchRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preview_id' => ['required', 'string', 'uuid'],
            'row_index' => ['required', 'integer', 'min:0'],
            'data' => ['required', 'array'],
            // Sengaja longgar: aturan domain per kolom divalidasi ulang oleh
            // service (validateRows), supaya pesan error identik dengan
            // jalur unggah file.
            'data.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'preview_id.required' => 'Preview ID wajib diisi',
            'preview_id.uuid' => 'Preview ID tidak valid',
            'row_index.required' => 'Baris yang diperbaiki wajib ditentukan',
            'row_index.integer' => 'Baris yang diperbaiki tidak valid',
            'data.required' => 'Data perbaikan wajib diisi',
            'data.array' => 'Data perbaikan tidak valid',
        ];
    }
}
