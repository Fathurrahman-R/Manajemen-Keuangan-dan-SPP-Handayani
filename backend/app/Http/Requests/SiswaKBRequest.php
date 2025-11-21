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
        return [
            'nis' => [
                'required',
                'max:20',
                'regex:/^[0-9]+$/',
                'min:4'
            ],
            'nama' => [
                'required',
                'max:100',
                'regex:/^[A-Za-zÀ-ÿ\'\s]+$/u'
            ],
            'jenis_kelamin' => [
                'required',
                'in:Laki-laki,Perempuan'
            ],
            'tempat_lahir' => [
                'required',
                'max:100'
            ],
            'tanggal_lahir' => [
                'required',
                'date',
                'before:today',
                'after:1989-12-30'
            ],
            'agama' => [
                'required',
                'max:50',

            ],
            'alamat' => [
                'required'
            ],
            'wali_id' => [
                'required',
                'exists:walis,id'
            ],
            'kelas_id' => [
                'required',
                'exists:kelas,id'
            ],
            'kategori_id' => [
                'required',
                'exists:kategoris,id'
            ],
            'status' => [
                'nullable',
                'in:Aktif,Lulus,Pindah,Keluar'
            ],
            'keterangan' => [
                'nullable'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.max' => 'NIS maksimal 20 karakter.',
            'nis.min' => 'NIS minimal 4 karakter.',
            'nis.regex' => 'NIS hanya boleh berisi angka.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 100 karakter.',
            'nama.regex' => 'Nama hanya boleh berisi huruf dan spasi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tempat_lahir.max' => 'Tempat lahir maksimal 100 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'tanggal_lahir.after' => 'Tanggal lahir terlalu lama.',
            'agama.required' => 'Agama wajib diisi.',
            'agama.max' => 'Agama maksimal 50 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'wali_id.required' => 'Wali wajib diisi.',
            'wali_id.exists' => 'Wali tidak ditemukan.',
            'kelas_id.required' => 'Kelas wajib diisi.',
            'kelas_id.exists' => 'Kelas tidak ditemukan.',
            'kategori_id.required' => 'Kategori wajib diisi.',
            'kategori_id.exists' => 'Kategori tidak ditemukan.',
            'status.in' => 'Status harus salah satu: Aktif, Lulus, Pindah, Keluar.'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ], 400));
    }
}
