<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class TahunAjaranRequest extends FormRequest
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
            'nama' => [
                'required',
                'string',
                'max:9',
                'regex:/^\d{4}\/\d{4}$/',
            ],
            'tanggal_mulai' => [
                'required',
                'date',
            ],
            'tanggal_selesai' => [
                'required',
                'date',
                'after:tanggal_mulai',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama tahun ajaran wajib diisi.',
            'nama.max' => 'Nama tahun ajaran maksimal 9 karakter.',
            'nama.regex' => 'Format nama harus YYYY/YYYY.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date' => 'Tanggal selesai harus berupa tanggal yang valid.',
            'tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            "errors" => $validator->getMessageBag()
        ], 422));
    }
}
