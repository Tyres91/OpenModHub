<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'block_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $target = $this->route('user');
            $authUser = $this->user();

            if (! $target instanceof User || ! $authUser instanceof User) {
                return;
            }

            if ($target->id === $authUser->id) {
                $validator->errors()->add('user', __('messages.admin.users.cannot_block_self'));
            }

            if ($target->hasRole('admin')) {
                $otherAdmins = User::query()
                    ->where('id', '!=', $target->id)
                    ->whereNull('blocked_at')
                    ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
                    ->exists();

                if (! $otherAdmins) {
                    $validator->errors()->add('user', __('messages.admin.users.cannot_block_last_admin'));
                }
            }
        });
    }
}
