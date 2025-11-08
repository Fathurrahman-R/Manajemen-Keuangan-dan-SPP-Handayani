<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SiswaMIRequest extends FormRequest
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
            'nis'=>[
                'required',
                'max:20'
            ],
            'nisn'=>[
                'required',
                'max:20'
            ],
            'nama'=>[
                'required',
                'max:100'
            ],
            'jenis_kelamin'=>[
                'required',
            ],
            'tempat_lahir'=>[
                'required',
                'max:100'
            ],
            'tanggal_lahir'=>[
                'required',
                'date'
            ],
            'agama'=>[
                'required',
                'max:50'
            ],
            'alamat'=>[
                'required'
            ],
            'ayah'=>[
                'nullable'
            ],
            'ibu'=>[
                'nullable'
            ],
            'wali'=>[
                'required'
            ],
            'jenjang'=>[
                'required'
            ],
            'kelas'=>[
                'required'
            ],
            'kategori'=>[
                'required'
            ],
            'asal_sekolah'=>[
                'nullable',
                'max:150'
            ],
            'kelas_diterima'=>[
                'nullable',
                'max:10'
            ],
            'tahun_diterima'=>[
                'nullable',
                'date_format:Y'
            ],
            'status'=>[

            ],
            'keterangan'=>[
                'nullable'
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ],400));
    }
}
