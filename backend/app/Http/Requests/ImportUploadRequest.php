<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'], // 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File wajib diunggah',
            'file.mimes' => 'Format file harus .xlsx atau .csv',
            'file.max' => 'Ukuran file maksimal 5MB',
        ];
    }
}
