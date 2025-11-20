<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class JenisTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by middleware
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nama')) {
            $this->merge(['nama' => trim($this->input('nama'))]);
        }
    }

    public function rules(): array
    {
        return [
            'nama' => ['required','string','min:3','max:100'],
            'jatuh_tempo' => ['required','date','date_format:Y-m-d'],
            'jumlah' => ['required','numeric','regex:/^\d{1,10}(\.\d{1,2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'nama wajib diisi.',
            'nama.min' => 'nama minimal 3 karakter.',
            'nama.max' => 'nama maksimal 100 karakter.',
            'jatuh_tempo.required' => 'jatuh_tempo wajib diisi.',
            'jatuh_tempo.date' => 'format tanggal tidak valid.',
            'jatuh_tempo.date_format' => 'format tanggal harus Y-m-d.',
            'jumlah.required' => 'jumlah wajib diisi.',
            'jumlah.numeric' => 'jumlah harus numerik.',
            'jumlah.regex' => 'format jumlah tidak valid (maks 10 digit dan 2 desimal).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag()
        ], 400));
    }
}
