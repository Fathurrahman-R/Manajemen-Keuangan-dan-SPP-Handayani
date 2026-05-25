<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        if ($this->isMethod('POST')) {
            return [
                'username' => 'required|string|max:100|unique:users,username',
                'password' => 'required|string|min:8|max:100',
                'branch_id' => 'required|integer|exists:branches,id',
                'roles' => 'required|array|min:1',
                'roles.*' => 'required|string|exists:roles,name',
            ];
        }

        // PUT/PATCH — all fields optional
        return [
            'username' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'password' => 'sometimes|string|min:8|max:100',
            'branch_id' => 'sometimes|integer|exists:branches,id',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'required|string|exists:roles,name',
        ];
    }
}
