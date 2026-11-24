<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

class Hcaptcha extends SiteverifyCaptcha
{
    public function handle(): string
    {
        return 'hcaptcha';
    }

    public function field(): string
    {
        return 'h-captcha-response';
    }

    protected function endpoint(): string
    {
        return 'https://api.hcaptcha.com/siteverify';
    }

    protected function payload(string $token, ?string $ip): array
    {
        return parent::payload($token, $ip) + ['sitekey' => $this->siteKey()];
    }
}
