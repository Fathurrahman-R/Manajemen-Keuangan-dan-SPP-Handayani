<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SiswaMIUpdateRequest extends FormRequest
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
            'ayah_nama' => [
                'required',
                'max:100'
            ],
            'ayah_pendidikan' => [
                'nullable',
                'max:50'
            ],
            'ayah_pekerjaan' => [
                'nullable',
                'max:100'
            ],
            'ibu_nama' => [
                'required',
                'max:100'
            ],
            'ibu_pendidikan' => [
                'nullable',
                'max:50'
            ],
            'ibu_pekerjaan' => [
                'nullable',
                'max:100'
            ],
            'kelas_id' => [
                'required',
                'exists:kelas,id'
            ],
            'kategori_id' => [
                'required',
                'exists:kategoris,id'
            ],
            'asal_sekolah' => [
                'nullable',
                'max:150'
            ],
            'kelas_diterima' => [
                'nullable',
                'max:10'
            ],
            'tahun_diterima' => [
                'nullable',
                'date_format:Y',
                'before_or_equal:' . date('Y')
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

//    public function messages(): array
//    {
//        return [
//            'nama.required' => 'Nama wajib diisi.',
//            'nama.max' => 'Nama maksimal 100 karakter.',
//            'nama.regex' => 'Nama hanya boleh berisi huruf dan spasi.',
//            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
//            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
//            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
//            'tempat_lahir.max' => 'Tempat lahir maksimal 100 karakter.',
//            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
//            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
//            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
//            'tanggal_lahir.after' => 'Tanggal lahir terlalu lama.',
//            'agama.required' => 'Agama wajib diisi.',
//            'agama.max' => 'Agama maksimal 50 karakter.',
//            'alamat.required' => 'Alamat wajib diisi.',
//            'ayah_nama.required' => 'Nama ayah wajib diisi.',
//            'ayah_nama.max' => 'Nama ayah maksimal 100 karakter.',
//            'ayah_pendidikan.max' => 'Pendidikan ayah maksimal 50 karakter.',
//            'ayah_pekerjaan.max' => 'Pekerjaan ayah maksimal 100 karakter.',
//            'ibu_nama.required' => 'Nama ibu wajib diisi.',
//            'ibu_nama.max' => 'Nama ibu maksimal 100 karakter.',
//            'ibu_pendidikan.max' => 'Pendidikan ibu maksimal 50 karakter.',
//            'ibu_pekerjaan.max' => 'Pekerjaan ibu maksimal 100 karakter.',
//            'wali_nama.required' => 'Nama wali wajib diisi.',
//            'wali_nama.max' => 'Nama wali maksimal 100 karakter.',
//            'wali_pekerjaan.max' => 'Pekerjaan wali maksimal 100 karakter.',
//            'wali_alamat.required' => 'Alamat wali wajib diisi.',
//            'wali_no_hp.required' => 'Nomor HP wali wajib diisi.',
//            'wali_no_hp.max' => 'Nomor HP wali maksimal 100 karakter.',
//            'kelas_id.required' => 'Kelas wajib diisi.',
//            'kelas_id.exists' => 'Kelas tidak ditemukan.',
//            'kategori_id.required' => 'Kategori wajib diisi.',
//            'kategori_id.exists' => 'Kategori tidak ditemukan.',
//            'asal_sekolah.max' => 'Asal sekolah maksimal 150 karakter.',
//            'kelas_diterima.max' => 'Kelas diterima maksimal 10 karakter.',
//            'tahun_diterima.date_format' => 'Format tahun diterima harus YYYY.',
//            'tahun_diterima.before_or_equal' => 'Tahun diterima tidak boleh melebihi tahun sekarang.',
//            'status.in' => 'Status harus salah satu: Aktif, Lulus, Pindah, Keluar.'
//        ];
//    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ], 400));
    }
}
