<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class NotificationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() != null;
    }

    public function rules(): array
    {
        return [
            'tagihan_baru_enabled' => 'sometimes|boolean',
            'reminder_enabled' => 'sometimes|boolean',
            'kwitansi_enabled' => 'sometimes|boolean',
            'overdue_enabled' => 'sometimes|boolean',
            'reminder_days_before' => 'sometimes|array|min:1',
            'reminder_days_before.*' => 'integer|min:1|max:30',
            'overdue_interval_days' => 'sometimes|integer|min:1',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response(['errors' => $validator->getMessageBag()], 422));
    }
}
