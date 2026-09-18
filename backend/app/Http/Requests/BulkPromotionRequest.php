<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkPromotionRequest extends FormRequest
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
        return [
            'kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id',
            ],
            'tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:tahun_ajarans,id',
            ],
            // Opsional. Kalau diisi, hanya siswa dalam daftar ini yang dipromosikan.
            // Tanpa ini seluruh siswa eligible di kelas ikut naik — termasuk yang
            // di UI ditandai tinggal kelas/lulus, sehingga batch mencatat mereka
            // sebagai naik_kelas dan undo jadi salah.
            'siswa_ids' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'siswa_ids.*' => [
                'integer',
                'exists:siswas,id',
            ],
            // Opsional. Wajib diisi kalau level berikutnya punya lebih dari satu
            // kelas sejajar (mis. TK level 1 = MATAHARI/BINTANG/BULAN) — tanpa
            // ini KenaikanKelasService::getNextKelas() tidak bisa menebak
            // tujuan mana yang benar dan akan menolak dengan 422.
            'target_kelas_id' => [
                'sometimes',
                'integer',
                'exists:kelas,id',
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag(),
        ], 422));
    }
}
