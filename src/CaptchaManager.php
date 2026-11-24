<?php

namespace NineteenNinetyFour\FormsCaptcha;

use NineteenNinetyFour\FormsCaptcha\Contracts\Captcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\Hcaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\NullCaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\Recaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\RecaptchaV3;
use NineteenNinetyFour\FormsCaptcha\Drivers\Turnstile;
use Statamic\Support\Manager;

/**
 * @method Captcha driver(?string $name = null)
 */
class CaptchaManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->app['config']['forms-captcha.driver'] ?: 'null';
    }

    protected function getConfig($name)
    {
        return array_merge($this->app['config']['forms-captcha'] ?? [], ['driver' => $name]);
    }

    protected function invalidImplementationMessage($name)
    {
        return "Captcha driver [{$name}] is not defined.";
    }

    protected function invalidDriverMessage($driver, $name)
    {
        return "Captcha driver [{$driver}] is not defined.";
    }

    /**
     * Check a token with the configured driver.
     *
     * Tokens can only be checked once, so the answer is kept for the rest of the request:
     * a form validated twice in one request doesn't fail the second time.
     */
    public function verify(?string $token, ?string $ip = null): Verification
    {
        $request = $this->app['request'];
        $key = 'forms-captcha.verified.'.$this->getDefaultDriver().'.'.hash('sha256', (string) $token);

        if ($token !== null && $request->attributes->has($key)) {
            return $request->attributes->get($key);
        }

        $verification = $this->driver()->verify($token, $ip);

        if ($token !== null) {
            $request->attributes->set($key, $verification);
        }

        return $verification;
    }

    public function createNullDriver()
    {
        return new NullCaptcha;
    }

    public function createTurnstileDriver(array $config)
    {
        return new Turnstile($config);
    }

    public function createHcaptchaDriver(array $config)
    {
        return new Hcaptcha($config);
    }

    public function createRecaptchaDriver(array $config)
    {
        return new Recaptcha($config);
    }

    public function createRecaptchaV3Driver(array $config)
    {
        return new RecaptchaV3($config);
    }
}
