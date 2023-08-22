<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecaptchaValidation implements ImplicitRule
{
    /**
     * Derives the hostname from the `app.url` config value.
     * 
     * @return string|false
     */
    static public function getHostname()
    {
        $appUrl = config('app.url');
        if (blank($appUrl)) {
            logger()->warning(self::class.': app.url is not set; ReCaptcha hostname verification disabled');

            return false;
        }

        $parsedUrl = parse_url($appUrl);
        return Arr::get($parsedUrl, 'host', false);
    }

    /**
     * Comparison against expected hostname.
     * 
     * @param  string $hostname
     * @return bool  
     */
    public function verifyHostname($hostname)
    {
        $expectedHostname = self::getHostname();

        if (filled($expectedHostname)) {
            if (false === Str::of($expectedHostname)->exactly($hostname)) {
                return false;
            }
        } else {
            logger()->error(self::class.': hostname detection failed; ReCaptcha hostname verification disabled');
        }

        return true;
    }

    /**
     * Verifies the ReCaptcha Request with the ReCaptcha Service
     * 
     * @param  string $secretKey
     * @return \ReCaptcha\Response 
     */
    public function verify($token)
    {
        $recaptcha = new \ReCaptcha\ReCaptcha(
            config('recaptcha.secret_key')
        );

        $recaptchaResponse = $recaptcha
        ->verify(
            $token,
            request()->getClientIp()
        );

        return $recaptchaResponse;
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
        $recaptchaResponse = $this->verify($value);

        logger()->debug(self::class.': response', [
            'response' => $recaptchaResponse->toArray()
        ]);

        if (false === $this->verifyHostname($recaptchaResponse->getHostname())) {
            logger()->debug(self::class.': hostname verification failed', [
                'hostname' => self::getHostname(),
            ]);

            return false;
        }

        if (false === $recaptchaResponse->isSuccess()) {
            logger()->debug(self::class.': ReCaptcha response claims no success');
            
            return false;
        }

        $minScore = (float) config('recaptcha.min_score');
        if ($recaptchaResponse->getScore() < $minScore) {
            logger()->debug(self::class.': below min score', [
                'minScore' => $minScore,
                'score'    => $recaptchaResponse->getScore()
            ]);

            return false;
        }

        logger()->debug(self::class.': validation passed');

        return true;
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
