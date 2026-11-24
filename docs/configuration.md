# Configuration

The provider and its keys are set once, in `.env`, for every form. Each Captcha field then has a few settings of its own.

## The config file

To change more than the keys, publish the config:

```bash
php artisan vendor:publish --tag=forms-captcha-config
```

```php
// config/forms-captcha.php
return [
    'driver' => env('FORMS_CAPTCHA_DRIVER', 'turnstile'), // or 'null' here, 'none' in .env
    'site_key' => env('FORMS_CAPTCHA_SITE_KEY'),
    'secret_key' => env('FORMS_CAPTCHA_SECRET_KEY'),

    // reCAPTCHA v3 only
    'score' => env('FORMS_CAPTCHA_SCORE', 0.5), // visits scoring under this are low scores
    'low_scores' => 'reject',                    // what new fields do with them: "reject" or "spam"
    'action' => 'submit',                        // tokens made for another action are refused

    'timeout' => 5, // seconds to wait for the provider
];
```

## Each field's settings

Each Captcha field has its own settings in the builder:

| Setting | Providers | |
| --- | --- | --- |
| Label | all | Shown in the builder and in the Control Panel. The widget is shown without one. |
| Theme | Turnstile, hCaptcha, reCAPTCHA v2 | Auto (follows light or dark mode), Light or Dark. |
| Size | Turnstile, hCaptcha, reCAPTCHA v2 | Normal or Compact. Turnstile also has Full width, which fills the space it's given (from 300 pixels up): the best fit for narrow spaces, such as a sign-up form in a footer. |
| Low scores | reCAPTCHA v3 | **Show an error**, or **Keep as spam**: the visitor is told it went through, and the submission is kept as spam for review. On a form that doesn't store submissions, it's dropped. |

## Content Security Policy

The widget is a few attributes and a small inline script, which loads the provider's own script once, from the provider's own URL (never one taken from the page). If you use Laravel's `Vite::useCspNonce()`, the script gets the nonce. Allow your provider's domains too:

- Turnstile: `https://challenges.cloudflare.com` (script-src, frame-src)
- hCaptcha: `https://hcaptcha.com`, `https://*.hcaptcha.com` (script-src, frame-src, style-src, connect-src)
- reCAPTCHA: `https://www.google.com/recaptcha/`, `https://www.gstatic.com/recaptcha/` (script-src, frame-src)
