<?php

namespace NineteenNinetyFour\FormsCaptcha\Contracts;

use NineteenNinetyFour\FormsCaptcha\Verification;

interface Captcha
{
    /**
     * The HTML that goes inside the form, where the widget should appear.
     *
     * @param  array{theme?: string, size?: string}  $options  from the field's settings
     */
    public function widget(array $options = []): string;

    /**
     * Check the token the widget put in the form.
     *
     * A failed check, or a provider that can't be reached, is a failed Verification.
     */
    public function verify(?string $token, ?string $ip = null): Verification;

    /**
     * The name of the form input the widget fills with its token.
     */
    public function field(): string;

    /**
     * Whether the driver has everything it needs (its keys) to work.
     */
    public function configured(): bool;
}
