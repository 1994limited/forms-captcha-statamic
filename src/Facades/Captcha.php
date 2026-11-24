<?php

namespace NineteenNinetyFour\FormsCaptcha\Facades;

use Illuminate\Support\Facades\Facade;
use NineteenNinetyFour\FormsCaptcha\CaptchaManager;

/**
 * @method static \NineteenNinetyFour\FormsCaptcha\Contracts\Captcha driver(?string $name = null)
 * @method static \NineteenNinetyFour\FormsCaptcha\Verification verify(?string $token, ?string $ip = null)
 * @method static CaptchaManager extend(string $driver, \Closure $callback)
 *
 * @see CaptchaManager
 */
class Captcha extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CaptchaManager::class;
    }
}
