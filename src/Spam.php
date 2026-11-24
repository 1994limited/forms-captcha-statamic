<?php

namespace NineteenNinetyFour\FormsCaptcha;

use Illuminate\Http\Request;

/**
 * Low reCAPTCHA v3 scores that a field let through to be kept as spam. They're
 * kept on the request, and the FormSubmitted listener marks the submission.
 */
class Spam
{
    const KEY = 'forms-captcha.spam';

    public static function flag(Request $request, Verification $verification): void
    {
        $request->attributes->set(static::KEY, $verification);
    }

    public static function flagged(Request $request): ?Verification
    {
        return $request->attributes->get(static::KEY);
    }

    public static function forget(Request $request): void
    {
        $request->attributes->remove(static::KEY);
    }
}
