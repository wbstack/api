<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecaptchaValidation implements ImplicitRule
{
    /**
     * @var \ReCaptcha\ReCaptcha
     */
    protected $recaptcha = null;

    /**
     * @var string
     */
    protected $hostname = null;

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
        $this->recaptcha = $this->buildRecaptcha(
            config('recaptcha.secret_key')
        );

        $this->minScore = (float) config('recaptcha.min_score');
        $this->initHostname();
    }

    /**
     * Initializes the $hostname property via config value `app.url`
     * 
     * @return bool
     */
    public function initHostname()
    {
        $appUrl = config('app.url');
        if (blank($appUrl)) {
            logger()->warning('app.url is not set; ReCaptcha hostname verification disabled', [self::class]);
            return false;
        }

        $parsedUrl = parse_url($appUrl);
        $this->hostname = Arr::get($parsedUrl, 'host', null);

        if (blank($this->hostname)) {
            logger()->error('hostname detection failed; ReCaptcha hostname verification disabled', [self::class]);
            return false;
        }

        return true;
    }

    /**
     * Compares passed string to initialized hostname.
     * Defaults to verifying anything if self::$hostname is not set.
     * 
     * @param  string $hostname
     * @return bool  
     */
    public function verifyHostname($hostname) {
        if (filled($this->hostname)) {
            if (
                ! Str::of($this->hostname)->exactly($hostname)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Builds ReCaptcha object
     * 
     * @param  string $secretKey
     * @return \ReCaptcha\ReCaptcha
     */
    public function buildRecaptcha($secretKey)
    {
        return new \ReCaptcha\ReCaptcha($secretKey);
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
        // @var \Recaptcha\Response
        $recaptchaResponse = $this->recaptcha
            ->verify(
                $value,
                request()->getClientIp()
            );

        logger()->debug(self::class.': response', [
            'response' => $recaptchaResponse->toArray()
        ]);

        if (! $this->verifyHostname($recaptchaResponse->getHostname())) {
            logger()->debug(self::class.': hostname verification failed', [
                'hostname'         => $this->hostname,
            ]);

            return false;
        }

        if (! $recaptchaResponse->isSuccess()) {
            logger()->debug(self::class.': ReCaptcha lib returned below min score', [
                'minScore' => $this->minScore,
            ]);
            
            return false;
        }

        if ($recaptchaResponse->getScore() < $this->minScore) {
            logger()->debug(self::class.': below min score', [
                'minScore' => $this->minScore,
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
