<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class BatchPaymentRequest extends FormRequest
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
            'kode_tagihan' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'kode_tagihan.*' => [
                'required',
                'string',
                'exists:tagihans,kode_tagihan',
            ],
            'metode' => [
                'required',
                'in:offline,online_midtrans',
            ],
            'pembayar' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_tagihan.required' => 'Field kode_tagihan wajib diisi.',
            'kode_tagihan.array' => 'Field kode_tagihan harus berupa array.',
            'kode_tagihan.min' => 'Minimal 1 tagihan harus dipilih.',
            'kode_tagihan.max' => 'Maksimal 50 tagihan dalam satu batch.',
            'kode_tagihan.*.required' => 'Kode tagihan wajib diisi.',
            'kode_tagihan.*.string' => 'Kode tagihan harus berupa string.',
            'kode_tagihan.*.exists' => 'Kode tagihan :input tidak ditemukan.',
            'metode.required' => 'Metode pembayaran wajib diisi.',
            'metode.in' => 'Metode harus offline atau online_midtrans.',
            'pembayar.required' => 'Nama pembayar wajib diisi.',
            'pembayar.string' => 'Nama pembayar harus berupa string.',
            'pembayar.max' => 'Pembayar maksimal 100 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ], 400));
    }
}
