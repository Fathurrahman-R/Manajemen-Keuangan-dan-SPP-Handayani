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
                'email' => [
                    'nullable', 'email', 'max:255',
                    Rule::unique('users', 'email')
                        ->where(fn($q) => $q->where('branch_id', $this->input('branch_id'))),
                ],
                'name' => 'nullable|string|max:255',
                'password' => 'required|string|min:8|max:100',
                'branch_id' => 'required|integer|exists:branches,id',
                'is_active' => 'sometimes|boolean',
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
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->where(fn($q) => $q->where('branch_id', $this->input('branch_id'))),
            ],
            'name' => 'sometimes|nullable|string|max:255',
            'password' => 'sometimes|string|min:8|max:100',
            'branch_id' => 'sometimes|integer|exists:branches,id',
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'required|string|exists:roles,name',
        ];
    }
}
