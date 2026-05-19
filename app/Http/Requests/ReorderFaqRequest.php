<?php

namespace App\Http\Requests;

use App\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;

class ReorderFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Faq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'faqs' => ['required', 'array'],
            'faqs.*.id' => ['required', 'integer', 'exists:faqs,id'],
            'faqs.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
