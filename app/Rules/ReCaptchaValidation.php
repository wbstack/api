<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ReCaptchaValidation implements ImplicitRule
{
    /**
     * @var string $secret Secret Site Key
     */
    protected $secret;

    /**
     * @var string $minScore Lowest score to pass (0.0 - 1.0)
     */
    protected $minScore;

    /**
     * @var string $appUrl App URL of client request
     */
    protected $appUrl;

    public function __construct($secret, $minScore, $appUrl) {
        $this->secret = $secret;
        $this->appUrl = $appUrl;
        $this->minScore = $minScore;
    }

    /**
     * Comparison against expected hostname.
     * 
     * @param  string $hostname
     * @return bool  
     */
    private function verifyHostname($hostname)
    {
        $parsedUrl = parse_url($this->appUrl);
        $expectedHostname = Arr::get($parsedUrl, 'host', null);

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
     * Verifies the ReCaptcha Request with the official ReCaptcha service library
     * 
     * @param  string $secretKey
     * @return \ReCaptcha\Response 
     */
    public function verify($token)
    {
        $recaptchaResponse = new \ReCaptcha\Response(false);

        try {
            $recaptcha = new \ReCaptcha\ReCaptcha(
                $this->secret
            );

            $recaptchaResponse = $recaptcha
            ->verify(
                $token,
                request()->getClientIp()
            );

            logger()->debug(self::class.': response', [
                'response' => $recaptchaResponse->toArray()
            ]);
        } catch(\Exception $e) {
            logger()->error(self::class.': Exception thrown by \Recaptcha\ReCaptcha::verify', [$e]);
        }

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

        if (false === $this->verifyHostname($recaptchaResponse->getHostname())) {
            return false;
        }

        if (false === $recaptchaResponse->isSuccess()) {
            return false;
        }

        if ($recaptchaResponse->getScore() < $this->minScore) {
            logger()->debug(self::class.': below min score', [
                'minScore' => $this->minScore,
                'score'    => $recaptchaResponse->getScore()
            ]);

            return false;
        }

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
