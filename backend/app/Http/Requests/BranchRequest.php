<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'location' => [
                'required',
                'string',
                'min:1',
                'max:150',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('location')) {
            $this->merge([
                'location' => trim($this->input('location')),
            ]);
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag(),
        ], 400));
    }
}
