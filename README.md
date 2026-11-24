# Forms Captcha for Statamic

A Captcha field for Statamic's forms. Drag it into a form in the forms builder and visitors see Cloudflare Turnstile, hCaptcha or Google reCAPTCHA. The token is checked on the server when the form is submitted, and a submission without a valid one is refused, like any other field that doesn't validate. With reCAPTCHA v3, a low score can keep the submission as spam instead, for you to review.

It's built on Statamic 6.44's forms (Forms 2): it's a form fieldtype, so it works with Statamic's own field rendering, your own form templates, Blade, multi-page forms and forms sent with `fetch()`.

## Features

- **One field, any provider.** Turnstile, hCaptcha, reCAPTCHA v2 (the "I'm not a robot" checkbox) or reCAPTCHA v3 (no checkbox, a score). Set the provider and keys once in `.env`; change provider later without touching a form or a template.
- **Checked on the server.** The token is sent to the provider as a validation rule, with a timeout. A missing, forged, expired or reused token fails, and so does a provider that can't be reached. The token is never stored with the submission.
- **Low scores as spam.** With reCAPTCHA v3, choose per field whether a low score shows an error or keeps the submission as spam. Spam is kept for review (if the form stores submissions), and no emails or connections are sent for it.
- **Your templates.** `{{ fields }}` shows the widget where the field is. Templates with their own inputs place it with `{{ forms_captcha }}`. Errors are under the field's handle, so `{{ error:captcha }}` works too.
- **AJAX forms.** After a form posted with `fetch()` or axios gets its response, the widget resets for the next try. reCAPTCHA v3 waits for its token before your submit handler runs.
- **Safe defaults.** Without keys, or with no driver set, there's no widget and every submission with a Captcha field is refused, so a form is never left open by mistake. On multi-page forms, the Captcha field's page can't be skipped. Precognition never uses up a token. Works with static caching and a Content Security Policy nonce.

## Requirements

Statamic 6.44 or later, PHP 8.3 or later, and an account with Cloudflare Turnstile, hCaptcha or Google reCAPTCHA.

## Installation

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

Locally, use your provider's [test keys](docs/installation.md#test-keys).

## Documentation

Everything else is in [the documentation](docs/README.md):

- [Installation](docs/installation.md) and [configuration](docs/configuration.md)
- [Templates](docs/templates.md) and [JavaScript](docs/javascript.md)
- [Low scores and spam](docs/spam.md), the [PHP API](docs/php-api.md) and [troubleshooting](docs/troubleshooting.md)
- [Coming from aryehraber/statamic-captcha](docs/migrating.md)

## Support

Email [hello@1994.co.uk](mailto:hello@1994.co.uk), or open an issue on GitHub.
