<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SiswaKBRequest extends FormRequest
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
        // Wali fields are only required if wali_id is not provided
        $waliFieldRequired = ! $this->filled('wali_id') ? 'required' : 'nullable';

        return [
            'nis' => [
                'required',
                'max:20',
                'regex:/^[0-9]+$/',
                'min:4',
            ],
            'nama' => [
                'required',
                'max:100',
                'regex:/^[A-Za-zÀ-ÿ\'\s]+$/u',
            ],
            'jenis_kelamin' => [
                'required',
                'in:Laki-laki,Perempuan',
            ],
            'tempat_lahir' => [
                'required',
                'max:100',
            ],
            'tanggal_lahir' => [
                'required',
                'date',
                'before:today',
                'after:1989-12-30',
            ],
            'agama' => [
                'required',
                'max:50',

            ],
            'alamat' => [
                'required',
            ],
            // optional parent linking ID
            'wali_id' => [
                'nullable',
                'integer',
                'exists:walis,id',
            ],
            'wali_nama' => [
                $waliFieldRequired,
                'max:100',
            ],
            'wali_pekerjaan' => [
                'nullable',
                'max:100',
            ],
            'wali_alamat' => [
                $waliFieldRequired,
            ],
            'wali_no_hp' => [
                $waliFieldRequired,
                'max:100',
            ],
            'wali_keterangan' => [
                'nullable',
            ],
            'wali_email' => [
                'nullable',
                'email:rfc',
            ],
            'kelas_id' => [
                'required',
                'exists:kelas,id',
            ],
            'kategori_id' => [
                'required',
                'exists:kategoris,id',
            ],
            'status' => [
                'nullable',
                'in:Aktif,Lulus,Pindah,Keluar',
            ],
            'keterangan' => [
                'nullable',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'wali_email.email' => 'Format email tidak valid',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag(),
        ], 400));
    }
}
