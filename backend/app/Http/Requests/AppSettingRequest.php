<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator; // ditambahkan
use Illuminate\Http\Exceptions\HttpResponseException; // ditambahkan

class AppSettingRequest extends FormRequest
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
            'nama_sekolah'=>[
                'required',
                'string',
                'max:255',
            ],
            'lokasi'=>[
                'required',
                'string',
                'max:100',
            ],
            'alamat'=>[
                'required',
            ],
            'email'=>[
                'required',
                'string',
                'email',
                'max:100',
            ],
            'telepon'=>[
                'required',
                'max:20',
            ],
            'kepala_sekolah'=>[
                'required',
                'string',
                'max:100',
            ],
            'bendahara'=>[
                'required',
                'string',
                'max:100',
            ],
            'kode_pos'=>[
                'required',
                'max:15',
                'regex:/[0-9]/'
            ],
            'logo'=>[
                'file',
                'mimes:jpg,png',
            ]
        ];
    }

//    public function messages(): array
//    {
//        return [
//            'nama_sekolah.required' => 'Nama sekolah wajib diisi.',
//            'nama_sekolah.string' => 'Nama sekolah harus berupa teks.',
//            'nama_sekolah.max' => 'Nama sekolah maksimal 255 karakter.',
//            'lokasi.required' => 'Lokasi wajib diisi.',
//            'lokasi.string' => 'Lokasi harus berupa teks.',
//            'lokasi.max' => 'Lokasi maksimal 100 karakter.',
//            'alamat.required' => 'Alamat wajib diisi.',
//            'email.required' => 'Email wajib diisi.',
//            'email.string' => 'Email harus berupa teks.',
//            'email.email' => 'Format email tidak valid.',
//            'email.max' => 'Email maksimal 100 karakter.',
//            'telepon.required' => 'Telepon wajib diisi.',
//            'telepon.max' => 'Telepon maksimal 20 karakter.',
//            'kepala_sekolah.required' => 'Nama kepala sekolah wajib diisi.',
//            'kepala_sekolah.string' => 'Nama kepala sekolah harus berupa teks.',
//            'kepala_sekolah.max' => 'Nama kepala sekolah maksimal 100 karakter.',
//            'bendahara.required' => 'Nama bendahara wajib diisi.',
//            'bendahara.string' => 'Nama bendahara harus berupa teks.',
//            'bendahara.max' => 'Nama bendahara maksimal 100 karakter.',
//            'kode_pos.required' => 'Kode pos wajib diisi.',
//            'kode_pos.max' => 'Kode pos maksimal 15 karakter.',
//            'kode_pos.regex' => 'Kode pos hanya boleh berisi angka.',
//            'logo.required' => 'Logo wajib diunggah.',
//            'logo.file' => 'Logo harus berupa file.',
//            'logo.mimes' => 'Logo harus berformat PNG.',
//        ];
//    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag()
        ], 400));
    }
}
