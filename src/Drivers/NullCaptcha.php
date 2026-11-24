<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

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
