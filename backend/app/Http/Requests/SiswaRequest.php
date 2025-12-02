<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class SiswaRequest extends FormRequest
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
        $requiredMI = request('jenjang') == 'mi' ? 'required' : 'sometimes';
        $requiredOther = request('jenjang') != 'mi' ? 'required' : 'sometimes';
        return [
            'nis' => [
                'required',
                'max:20',
                'regex:/^[0-9]+$/',
                'min:4'
            ],
            'nisn' => [
                $requiredMI,
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
            // nested ayah
            'ayah_nama' => [
                $requiredMI,
                'max:100'
            ],
            'ayah_pendidikan_terakhir' => [
                $requiredMI,
                'nullable',
                'max:50'
            ],
            'ayah_pekerjaan' => [
                $requiredMI,
                'nullable',
                'max:100'
            ],
            // nested ibu
            'ibu_nama' => [
                $requiredMI,
                'max:100'
            ],
            'ibu_pendidikan_terakhir' => [
                $requiredMI,
                'nullable',
                'max:50'
            ],
            'ibu_pekerjaan' => [
                $requiredMI,
                'nullable',
                'max:100'
            ],
            'wali_nama' => [
                $requiredMI,
                'max:100'
            ],
            'wali_agama' => [
                $requiredMI,
                'nullable',
            ],
            'wali_jenis_kelamin' => [
                $requiredMI,
                'nullable',
            ],
            'wali_pendidikan_terakhir' => [
                $requiredMI,
                'nullable',
            ],
            'wali_pekerjaan' => [
                $requiredMI,
                'nullable',
                'max:100'
            ],
            'wali_alamat' => [
                $requiredMI
            ],
            'wali_no_hp' => [
                $requiredMI,
                'max:100'
            ],
            'wali_keterangan' => [
                'nullable'
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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag()
        ], 400));
    }
}
