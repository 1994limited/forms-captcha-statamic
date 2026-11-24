<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

use Illuminate\Support\Facades\Log;
use NineteenNinetyFour\FormsCaptcha\Contracts\Captcha;
use NineteenNinetyFour\FormsCaptcha\Verification;

/**
 * Shows nothing and lets everything through, for local development and tests.
 */
class NullCaptcha implements Captcha
{
    public function widget(array $options = []): string
    {
        return '';
    }

    public function verify(?string $token, ?string $ip = null): Verification
    {
        if (app()->isProduction()) {
            Log::warning('Forms Captcha is using the null driver in production, so captchas are not being checked. Set FORMS_CAPTCHA_DRIVER.');
        }

        return Verification::passed();
    }

    public function field(): string
    {
        return 'captcha';
    }

    public function configured(): bool
    {
        return true;
    }
}
