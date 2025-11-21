<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class TagihanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user() != null; // otorisasi dasar sudah dihandle middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis_tagihan_id' => [
                'required',
                'exists:jenis_tagihans,id'
            ],
            'jenjang' => [
                'required',
                'in:MI,KB,TK'
            ],
            'kelas_id' => [
                'required',
                'exists:kelas,id'
            ],
            'kategori_id' => [
                'required',
                'exists:kategoris,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_tagihan_id.required' => 'Jenis tagihan wajib diisi.',
            'jenis_tagihan_id.exists' => 'Jenis tagihan tidak ditemukan.',
            'jenjang.required' => 'Jenjang wajib diisi.',
            'jenjang.in' => 'Jenjang harus salah satu dari MI, KB, atau TK.',
            'kelas_id.required' => 'Kelas wajib diisi.',
            'kelas_id.exists' => 'Kelas tidak ditemukan.',
            'kategori_id.required' => 'Kategori wajib diisi.',
            'kategori_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ],400));
    }
}
