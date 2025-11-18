<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
                'string',
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
                'string',
                'max:15',
                'regex:/[0-9]/'
            ],
            'logo'=>[
                'required',
                'string',
                'max:255',
                'file',
                'mimes:png',
                'max:2048',
            ]
        ];
    }
}
