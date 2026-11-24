# Installation

## Requirements

Statamic 6.44 or later, PHP 8.3 or later, and an account with Cloudflare Turnstile, hCaptcha or Google reCAPTCHA.

## Installing

```bash
composer require 1994/forms-captcha-statamic
```

Then add your provider and keys to `.env`:

```env
FORMS_CAPTCHA_DRIVER=turnstile
FORMS_CAPTCHA_SITE_KEY=
FORMS_CAPTCHA_SECRET_KEY=
```

`FORMS_CAPTCHA_DRIVER` is `turnstile`, `hcaptcha`, `recaptcha` (v2 checkbox), `recaptcha_v3` or `none`. `none` shows nothing and lets everything through: use it for local development if you don't want to use your provider's test keys. It logs a warning if it's used in production. A blank setting counts as `turnstile`, so a missing setting never leaves a form open.

Open a form in the Control Panel, drag **Captcha** from the field list (it's under Structure) into the form, and save. That's it: the form now shows the widget, and checks it.

On a multi-page form, the field is checked on the page it's on, and the submission can't be finished until it has passed. The last page is the best place for it: the visitor has done the work by then, and a reCAPTCHA v3 token is fetched as that page is sent.

## Test keys

Each provider publishes keys for testing, which work on any domain, `localhost` and `.test` included:

| Provider | Site key | Secret key | |
| --- | --- | --- | --- |
| Turnstile | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` | always passes |
| Turnstile | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` | always fails |
| hCaptcha | `10000000-ffff-ffff-ffff-000000000001` | `0x0000000000000000000000000000000000000000` | always passes |
| reCAPTCHA v2 | `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI` | `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe` | always passes |

reCAPTCHA v3 has no test keys. Use real keys with `localhost` added to their domains.

## Updating

```bash
composer update 1994/forms-captcha-statamic
```

See the [changelog](../CHANGELOG.md) for what's changed. If you've published the field's view, compare it with the new one in `vendor/1994/forms-captcha-statamic/resources/views/field.blade.php`.
