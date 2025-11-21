<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class BayarLunasRequest extends FormRequest
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
