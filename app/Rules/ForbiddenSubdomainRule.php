<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ForbiddenSubdomainRule implements Rule {
    private $badWords;

    private $subdomainSuffix;

    const ERROR_MESSAGE = 'The subdomain contains a forbidden word.';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $badWords, string $subdomainSuffix) {
        $this->badWords = $badWords;
        $this->subdomainSuffix = $subdomainSuffix;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value) {
        $matches = [];
        $regexp = '/^([a-z0-9-]+)' . preg_quote($this->subdomainSuffix) . '$/';
        preg_match($regexp, $value, $matches, PREG_OFFSET_CAPTURE);

        if (count($matches) !== 2) {
            return false;
        }

        $subdomain = $matches[1][0];

        return $value !== null && ! in_array($subdomain, $this->badWords);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return self::ERROR_MESSAGE;
    }
}
