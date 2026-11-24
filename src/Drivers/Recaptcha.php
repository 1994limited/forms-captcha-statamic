<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

/**
 * Google reCAPTCHA v2, the "I'm not a robot" checkbox.
 */
class Recaptcha extends SiteverifyCaptcha
{
    public function handle(): string
    {
        return 'recaptcha';
    }

    public function field(): string
    {
        return 'g-recaptcha-response';
    }

    protected function endpoint(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }
}
