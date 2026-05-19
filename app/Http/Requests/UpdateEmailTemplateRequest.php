<?php

namespace App\Http\Requests;

use App\Models\EmailTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = EmailTemplate::find($this->route('emailTemplate'));

        return $this->user()?->can('update', $template) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_en' => ['required', 'string', 'max:255'],
            'subject_de' => ['required', 'string', 'max:255'],
            'body_en' => ['required', 'string'],
            'body_de' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
