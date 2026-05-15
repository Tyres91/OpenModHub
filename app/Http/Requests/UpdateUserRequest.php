<?php

namespace App\Http\Requests;

use App\Models\Rank;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
            'locale' => ['nullable', 'string', 'max:10', Rule::in(array_keys(config('locales.available')))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'rank_id' => ['nullable', 'integer', Rule::exists(Rank::class, 'id')->where('is_special', true)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'slug')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            $authUser = $this->user();

            if (! $authUser || ! $user) {
                return;
            }

            $roleSlugs = collect($this->input('roles', []))->map(fn ($r) => strtolower($r))->all();

            if ($user->id === $authUser->id) {
                $currentAdminRoles = $authUser->roles()->where('slug', 'admin')->exists();
                $newAdminRoles = in_array('admin', $roleSlugs, true);

                if ($currentAdminRoles && ! $newAdminRoles) {
                    $validator->errors()->add('roles', 'You cannot remove your own admin role.');
                }
            }

            $adminRole = Role::query()->where('slug', 'admin')->first();
            if (! $adminRole) {
                return;
            }

            $targetHasAdmin = $user->roles()->where('slug', 'admin')->exists();
            $targetLosingAdmin = $targetHasAdmin && ! in_array('admin', $roleSlugs, true);

            if ($targetLosingAdmin) {
                $otherAdmins = User::query()
                    ->where('id', '!=', $user->id)
                    ->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                    ->exists();

                if (! $otherAdmins) {
                    $validator->errors()->add('roles', 'At least one admin must remain in the system.');
                }
            }
        });
    }
}
