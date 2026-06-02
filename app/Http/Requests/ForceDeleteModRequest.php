<?php

namespace App\Http\Requests;

use App\Models\Mod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForceDeleteModRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mod = $this->route('mod');

        return $mod instanceof Mod && ($this->user()?->can('forceDelete', $mod) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $mod = $this->route('mod');

        return [
            'confirmation' => ['required', 'string', Rule::in([$mod instanceof Mod ? $mod->title : ''])],
        ];
    }
}
