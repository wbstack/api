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
    protected $minScore = null;

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

        $this->minScore = (float) config('recaptcha.min_score');
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
            if ($response->getScore() >= $this->minScore) {
                return true;
            } else {
                logger()->debug('recaptcha above min score', [
                    'class'    => self::class,
                    'minScore' => $this->minScore,
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
