<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidModImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $dimensions = @getimagesize($value->path());

        if ($dimensions === false) {
            $fail('The :attribute must be a valid image file.');

            return;
        }

        [$width, $height] = $dimensions;

        if ($width < 512 || $height < 512) {
            $fail('The :attribute must be at least 512x512 pixels.');
        }
    }
}
