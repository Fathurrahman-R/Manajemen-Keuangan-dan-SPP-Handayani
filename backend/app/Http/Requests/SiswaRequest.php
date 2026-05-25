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
        $isMI = request('jenjang') == 'MI' || $this->route('jenjang') == 'MI';
        $requiredMI = $isMI ? 'required' : 'nullable';
        $requiredOther = !$isMI ? 'required' : 'nullable';

        // Parent fields are only required if the corresponding parent ID is not provided
        $ayahFieldRequired = ($isMI && !$this->filled('ayah_id')) ? 'required' : 'nullable';
        $ibuFieldRequired = ($isMI && !$this->filled('ibu_id')) ? 'required' : 'nullable';
        $waliFieldRequired = (!$isMI && !$this->filled('wali_id')) ? 'required' : 'nullable';

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
                $ayahFieldRequired,
                'max:100'
            ],
            'ayah_pendidikan_terakhir' => [
                'nullable',
                'max:50'
            ],
            'ayah_pekerjaan' => [
                'nullable',
                'max:100'
            ],
            'ayah_email' => [
                'nullable',
                'email:rfc',
            ],
            // nested ibu
            'ibu_nama' => [
                $ibuFieldRequired,
                'max:100'
            ],
            'ibu_pendidikan_terakhir' => [
                'nullable',
                'max:50'
            ],
            'ibu_pekerjaan' => [
                'nullable',
                'max:100'
            ],
            'ibu_email' => [
                'nullable',
                'email:rfc',
            ],
            'wali_nama' => [
                $waliFieldRequired,
                'max:100'
            ],
            'wali_pekerjaan' => [
                'nullable',
                'max:100'
            ],
            'wali_alamat' => [
                $waliFieldRequired
            ],
            'wali_no_hp' => [
                $waliFieldRequired,
                'max:100'
            ],
            'wali_keterangan' => [
                'nullable'
            ],
            'wali_email' => [
                'nullable',
                'email:rfc',
            ],
            // optional parent linking IDs
            'ayah_id' => [
                'nullable',
                'integer',
                'exists:ayah,id'
            ],
            'ibu_id' => [
                'nullable',
                'integer',
                'exists:ibu,id'
            ],
            'wali_id' => [
                'nullable',
                'integer',
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

    public function messages(): array
    {
        return [
            'wali_email.email' => 'Format email tidak valid',
            'ayah_email.email' => 'Format email tidak valid',
            'ibu_email.email' => 'Format email tidak valid',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag()
        ], 400));
    }
}
