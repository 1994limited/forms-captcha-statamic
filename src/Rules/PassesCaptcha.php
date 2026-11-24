<?php

namespace NineteenNinetyFour\FormsCaptcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use NineteenNinetyFour\FormsCaptcha\CaptchaManager;
use NineteenNinetyFour\FormsCaptcha\Spam;

use function Statamic\trans as __;

class PassesCaptcha implements ValidationRule
{
    /**
     * Run even when there's no token, so a missing one fails.
     */
    public bool $implicit = true;

    /**
     * @param  string  $lowScores  "reject" or "spam": what a low reCAPTCHA v3 score does
     */
    public function __construct(protected string $lowScores = 'reject')
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $request = request();

        // Precognition checks fields as they're filled in. Tokens can only be checked once,
        // so the captcha waits for the real submission.
        if ($request->isPrecognitive()) {
            return;
        }

        $captcha = app(CaptchaManager::class);

        $verification = $captcha->verify($this->token($value, $captcha->driver()->field()), $request->ip());

        if ($verification->passed) {
            return;
        }

        if ($verification->lowScore && $this->lowScores === 'spam') {
            Spam::flag($request, $verification);

            return;
        }

        $fail(__('forms-captcha::messages.failed'));
    }

    /**
     * Widgets post their token under the provider's own name (cf-turnstile-response, for
     * example). Statamic only validates the form's own fields, so that's read from the
     * request. A token posted under the field's handle works too.
     */
    protected function token(mixed $value, string $input): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $token = request()->input($input);

        return is_string($token) && $token !== '' ? $token : null;
    }
}
