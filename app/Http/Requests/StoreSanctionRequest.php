<?php

namespace App\Http\Requests;

use App\Models\UserSanction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSanctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sanction', \App\Models\User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([UserSanction::TYPE_UPLOAD_BAN, UserSanction::TYPE_ACCOUNT_LOCK])],
            'reason' => ['required', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
