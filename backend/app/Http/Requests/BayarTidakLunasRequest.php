<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class BayarTidakLunasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jumlah'=>[
                'required',
                'numeric',
                'regex:/^\d{1,11}(\.\d{1,2})?$/'
            ],
            'metode'=>[
                'required',
                'in:Tunai,Non-Tunai'
            ],
            'pembayar'=>[
                'required',
                'max:100'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'jumlah.required' => 'Jumlah pembayaran wajib diisi.',
            'jumlah.numeric' => 'Jumlah pembayaran harus berupa angka.',
            'jumlah.regex' => 'Format jumlah tidak valid (maks 12 digit dan 2 desimal).',
            'metode.required' => 'Metode pembayaran wajib diisi.',
            'metode.in' => 'Metode pembayaran harus Tunai atau Non-Tunai.',
            'pembayar.required' => 'Nama pembayar wajib diisi.',
            'pembayar.max' => 'Nama pembayar maksimal 100 karakter.'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ],400));
    }
}
