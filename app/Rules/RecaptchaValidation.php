<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;

class RecaptchaValidation implements ImplicitRule
{
    /**
     * @var \ReCaptcha\ReCaptcha
     */
    protected $recaptcha = null;

    /**
     * @var float
     */
    protected $maxScore = null;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->recaptcha = new \ReCaptcha\ReCaptcha(
            config('recaptcha.secret_key')
        );

        $this->maxScore = (float) config('recaptcha.max_score');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $response = $this->recaptcha
            ->verify(
                $value,
                request()->getClientIp()
            );

        logger()->debug('recaptcha response', [
            'class' => self::class,
            'response' => $response->toArray()
        ]);

        if ($response->isSuccess()) {
            if ($response->getScore() <= $this->maxScore) {
                return true;
            } else {
                logger()->debug('recaptcha above max score', [
                    'class'    => self::class,
                    'maxScore' => $this->maxScore,
                    'score'    => $response->getScore()
                ]);
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'ReCaptcha validation failed.';
    }
}
