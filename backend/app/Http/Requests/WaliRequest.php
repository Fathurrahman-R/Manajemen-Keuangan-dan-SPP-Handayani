<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class WaliRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user() != null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $requiredOrSometimes = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'nama' => [
                $requiredOrSometimes,
                'string',
                'max:100'
            ],
//            'jenis_kelamin' => [
//                $requiredOrSometimes,
//                'string',
//                'in:Laki-laki,Perempuan'
//            ],
//            'agama' => [
//                $requiredOrSometimes,
//                'string',
//                'max:50'
//            ],
//            'pendidikan_terakhir' => [
//                $requiredOrSometimes,
//                'string',
//                'max:100'
//            ],
            'pekerjaan' => [
                'sometimes',
                'nullable',
                'string',
                'max:100'
            ],
            'alamat' => [
                $requiredOrSometimes,
                'string'
            ],
            'no_hp' => [
                $requiredOrSometimes,
                'max:20',
                'regex:/^[0-9+\-\s]+$/'
            ],
            'keterangan' => [
                'sometimes',
                'nullable',
            ]
        ];
    }

//    public function messages(): array
//    {
//        return [
//            'nama.required' => 'Nama wali wajib diisi.',
//            'nama.string' => 'Nama wali harus berupa teks.',
//            'nama.max' => 'Nama wali maksimal 100 karakter.',
//            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
//            'jenis_kelamin.string' => 'Jenis kelamin harus berupa teks.',
//            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
//            'agama.required' => 'Agama wajib diisi.',
//            'agama.string' => 'Agama harus berupa teks.',
//            'agama.max' => 'Agama maksimal 50 karakter.',
//            'pendidikan_terakhir.required' => 'Pendidikan terakhir wajib diisi.',
//            'pendidikan_terakhir.string' => 'Pendidikan terakhir harus berupa teks.',
//            'pendidikan_terakhir.max' => 'Pendidikan terakhir maksimal 100 karakter.',
//            'pekerjaan.string' => 'Pekerjaan harus berupa teks.',
//            'pekerjaan.max' => 'Pekerjaan maksimal 100 karakter.',
//            'alamat.required' => 'Alamat wajib diisi.',
//            'alamat.string' => 'Alamat harus berupa teks.',
//            'no_hp.required' => 'Nomor HP wajib diisi.',
//            'no_hp.max' => 'Nomor HP maksimal 20 karakter.',
//            'no_hp.regex' => 'Format nomor HP hanya boleh angka, spasi, plus (+) atau tanda minus (-).'
//        ];
//    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ], 400));
    }
}
