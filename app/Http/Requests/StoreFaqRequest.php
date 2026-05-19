<?php

namespace App\Http\Requests;

use App\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Faq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_en' => ['required', 'string', 'max:500'],
            'question_de' => ['required', 'string', 'max:500'],
            'answer_en' => ['required', 'string'],
            'answer_de' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
