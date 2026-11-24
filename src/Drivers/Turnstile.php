<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

/**
 * Cloudflare Turnstile.
 */
class Turnstile extends SiteverifyCaptcha
{
    public function handle(): string
    {
        return 'turnstile';
    }

    public function field(): string
    {
        return 'cf-turnstile-response';
    }

    protected function endpoint(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }
}
