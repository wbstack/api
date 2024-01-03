<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SettingCaptchaQuestions implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = json_decode($value, true);

        if ($value === null) {
            return false;
        }

        foreach (array_keys($value) as $question) {
            if (!is_string($question)) {
                return false;
            }
            if (strlen($question) > 200) {
                return false;
            }
        }

        foreach (array_values($value) as $answers) {
            if (!is_array($answers)) {
                return false;
            }
            if (count($answers) === 0) {
                return false;
            }
            foreach ($answers as $answer) {
                if (!is_string($answer)) {
                    return false;
                }
                if (strlen($answer) > 200) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Value must be JSON mapping of questions to an array of answers and neither question nor answers may be longer than 200 chars';
    }
}
