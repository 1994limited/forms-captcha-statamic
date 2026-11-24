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

    /**
     * Flexible fills the width it's given (from 300px up).
     */
    public function sizes(): array
    {
        return ['flexible', 'compact'];
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
