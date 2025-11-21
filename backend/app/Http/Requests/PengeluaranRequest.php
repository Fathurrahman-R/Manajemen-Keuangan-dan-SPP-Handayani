<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PengeluaranRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() != null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $requiredOrSometimes = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'tanggal' => [
                $requiredOrSometimes,
                'date',
                'date_format:Y-m-d',
            ],
            'uraian' => [
                $requiredOrSometimes,
            ],
            'jumlah' => [
                $requiredOrSometimes,
                'numeric',
                'decimal:12,2',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tanggal.date_format' => 'Format tanggal harus Y-m-d.',
            'uraian.required' => 'Uraian wajib diisi.',
            'uraian.string' => 'Uraian harus berupa teks.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.numeric' => 'Jumlah harus berupa angka.',
            'jumlah.decimal' => 'Format jumlah tidak valid (maks 12 digit dan 2 desimal).'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag(),
        ], 400));
    }
}
