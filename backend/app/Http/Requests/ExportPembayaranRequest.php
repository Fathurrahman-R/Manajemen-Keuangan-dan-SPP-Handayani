<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportPembayaranRequest extends FormRequest
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
            'tanggal_mulai' => ['nullable', 'date', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
        ];
    }
}
