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
            'jenis_kelamin' => [
                $requiredOrSometimes,
                'string',
                'in:Laki-laki,Perempuan'
            ],
            'agama' => [
                $requiredOrSometimes,
                'string',
                'max:50'
            ],
            'pendidikan_terakhir' => [
                $requiredOrSometimes,
                'string',
                'max:100'
            ],
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
                'string',
                'max:20',
                'regex:/^[0-9+\-\s]+$/'
            ],
            'keterangan' => [
                'sometimes',
                'nullable',
                'string'
            ]
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ], 400));
    }
}
