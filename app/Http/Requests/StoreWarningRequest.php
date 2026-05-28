<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('warn', \App\Models\User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'reason' => ['required', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
