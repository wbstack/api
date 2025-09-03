<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NonEmptyJsonRule implements ValidationRule {
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        $json = json_decode($value, true);
        if (!is_array($json) || empty($json)) {
            $fail("The {$attribute} field must be a non-empty JSON object.");
        }
    }
}
