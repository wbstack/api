<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReCaptcha\ReCaptcha;

class ReCaptchaValidation implements ImplicitRule {
    /**
     * @var \ReCaptcha\ReCaptcha instance
     */
    protected $recaptcha;

    /**
     * @var string Lowest score to pass (0.0 - 1.0)
     */
    protected $minScore;

    /**
     * @var string App URL of client request
     */
    protected $appUrl;

    public function __construct(ReCaptcha $recaptcha, $minScore, $appUrl) {
        $this->recaptcha = $recaptcha;
        $this->minScore = $minScore;
        $this->appUrl = $appUrl;
    }

    /**
     * Comparison against expected hostname.
     *
     * @param  string  $hostname
     * @return bool
     */
    private function verifyHostname($hostname) {
        $parsedUrl = parse_url($this->appUrl);
        $expectedHostname = Arr::get($parsedUrl, 'host', null);

        if (filled($expectedHostname)) {
            if (Str::of($expectedHostname)->exactly($hostname) === false) {
                return false;
            }
        } else {
            logger()->error('ReCaptcha hostname detection failed; will not verify hostname', [
                'class' => self::class,
            ]);
        }

        return true;
    }

    /**
     * Verifies the ReCaptcha Request with the official ReCaptcha service library
     *
     * @param  string  $secretKey
     * @return \ReCaptcha\Response
     */
    private function verify($token) {
        $recaptchaResponse = new \ReCaptcha\Response(false);

        try {
            $recaptchaResponse = $this->recaptcha
                ->verify(
                    $token,
                    request()->getClientIp()
                );

            logger()->debug('ReCaptcha response', [
                'class' => self::class,
                'response' => $recaptchaResponse->toArray(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Exception thrown by \Recaptcha\ReCaptcha::verify', [
                'class' => self::class,
                'exception' => $e,
            ]);
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
    public function passes($attribute, $value) {
        $recaptchaResponse = $this->verify($value);

        if ($this->verifyHostname($recaptchaResponse->getHostname()) === false) {
            return false;
        }

        if ($recaptchaResponse->isSuccess() === false) {
            return false;
        }

        if ($recaptchaResponse->getScore() < $this->minScore) {
            logger()->debug('ReCaptcha response below minScore', [
                'class' => self::class,
                'minScore' => $this->minScore,
                'score' => $recaptchaResponse->getScore(),
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
    public function message() {
        return 'ReCaptcha validation failed.';
    }
}
