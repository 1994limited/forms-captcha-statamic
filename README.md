# Forms Captcha for Statamic

A Captcha field for Statamic's forms. Drag it into a form in the forms builder and visitors see Cloudflare Turnstile, hCaptcha or Google reCAPTCHA. The token is checked on the server when the form is submitted, and a submission without a valid one is refused, like any other field that doesn't validate. With reCAPTCHA v3, a low score can keep the submission as spam instead, for you to review.

It's built on Statamic 6.44's forms (Forms 2): it's a form fieldtype, so it works with Statamic's own field rendering, your own form templates, Blade, multi-page forms and forms sent with `fetch()`.

## Features

- **One field, any provider.** Turnstile, hCaptcha, reCAPTCHA v2 (the "I'm not a robot" checkbox) or reCAPTCHA v3 (no checkbox, a score). Set the provider and keys once in `.env`; change provider later without touching a form or a template.
- **Checked on the server.** The token is sent to the provider as a validation rule, with a timeout. A missing, forged, expired or reused token fails, and so does a provider that can't be reached. The token is never stored with the submission.
- **Low scores as spam.** With reCAPTCHA v3, choose per field whether a low score shows an error or keeps the submission as spam. Spam is kept for review (if the form stores submissions), and no emails or connections are sent for it.
- **Your templates.** `{{ fields }}` shows the widget where the field is. Templates with their own inputs place it with `{{ forms_captcha }}`. Errors are under the field's handle, so `{{ error:captcha }}` works too.
- **AJAX forms.** After a form posted with `fetch()` or axios gets its response, the widget resets for the next try. reCAPTCHA v3 waits for its token before your submit handler runs.
- **Safe defaults.** Without keys there's no widget, and every submission with a Captcha field is refused, so a form is never left open by mistake. Precognition never uses up a token. Works with static caching and a Content Security Policy nonce.

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

`FORMS_CAPTCHA_DRIVER` is `turnstile`, `hcaptcha`, `recaptcha` (v2 checkbox), `recaptcha_v3` or `null`. The null driver shows nothing and lets everything through: use it for local development if you don't want to use your provider's test keys.

Open a form in the Control Panel, drag **Captcha** from the field list (it's under Structure) into the form, and save. That's it: the form now shows the widget, and checks it.

Put the field on the last page of a multi-page form. It's checked on the page it's on.

## Configuration

To change more than the keys, publish the config:

```bash
php artisan vendor:publish --tag=forms-captcha-config
```

```php
// config/forms-captcha.php
return [
    'driver' => env('FORMS_CAPTCHA_DRIVER', 'turnstile'),
    'site_key' => env('FORMS_CAPTCHA_SITE_KEY'),
    'secret_key' => env('FORMS_CAPTCHA_SECRET_KEY'),

    // reCAPTCHA v3 only
    'score' => env('FORMS_CAPTCHA_SCORE', 0.5), // visits scoring under this are low scores
    'low_scores' => 'reject',                    // what new fields do with them: "reject" or "spam"
    'action' => 'submit',                        // tokens made for another action are refused

    'timeout' => 5, // seconds to wait for the provider
];
```

Each Captcha field has its own settings in the builder:

| Setting | Providers | |
| --- | --- | --- |
| Label | all | Shown in the builder and in the Control Panel. The widget is shown without one. |
| Theme | Turnstile, hCaptcha, reCAPTCHA v2 | Auto (follows light or dark mode), Light or Dark. |
| Size | Turnstile, hCaptcha, reCAPTCHA v2 | Normal or Compact. |
| Low scores | reCAPTCHA v3 | **Show an error**, or **Keep as spam**: the visitor is told it went through, and the submission is kept as spam for review. On a form that doesn't store submissions, it's dropped. |

### Test keys

Each provider publishes keys for testing, which work on any domain, `localhost` and `.test` included:

| Provider | Site key | Secret key | |
| --- | --- | --- | --- |
| Turnstile | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` | always passes |
| Turnstile | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` | always fails |
| hCaptcha | `10000000-ffff-ffff-ffff-000000000001` | `0x0000000000000000000000000000000000000000` | always passes |
| reCAPTCHA v2 | `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI` | `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe` | always passes |

reCAPTCHA v3 has no test keys. Use real keys with `localhost` added to their domains.

## Templates

### Statamic's field rendering

Nothing to do. The Captcha field renders with the rest of the form's fields:

```antlers
{{ form:contact }}
    {{ fields }}
        {{ if is_informative }}
            {{ field }}
        {{ else }}
            <label for="{{ id }}">{{ display }}</label>
            {{ field }}
            {{ if error }}<p>{{ error }}</p>{{ /if }}
        {{ /if }}
    {{ /fields }}
    <button>Send</button>
{{ /form:contact }}
```

The Captcha field is `is_informative`, like a heading, so it's shown without a label. Its `{{ field }}` includes its own error message under the widget.

### Your own form template

Place the widget with `{{ forms_captcha }}` inside the form tag. Use `handle="…"` if a form has more than one. It includes its own error message, so there's no need to print `{{ error:captcha }}` as well (though you can, and leave it out of `{{ errors }}` lists).

```antlers
{{ form:contact }}
    <label>Email <input type="email" name="email" value="{{ old:email }}"></label>
    {{ forms_captcha }}
    <button>Send</button>
{{ /form:contact }}
```

The widget posts its token under the provider's own name (`cf-turnstile-response`, `h-captcha-response` or `g-recaptcha-response`). If you render the provider's widget yourself, post the token under one of those, or under the Captcha field's handle.

### Blade

```blade
<s:form:create in="contact">
    @foreach ($fields as $field)
        {!! $field['field'] !!}
    @endforeach

    {{-- or, with your own inputs --}}
    <s:forms_captcha :fields="$fields" />
</s:form:create>
```

### Changing the markup

The field is rendered by one Blade view, whatever your templates use. Publish it to change it:

```bash
php artisan vendor:publish --tag=forms-captcha-views
```

It's then at `resources/views/vendor/forms-captcha/field.blade.php`. `$captcha` is the widget, `$error` the message, `$id` the field's id.

The error message is in `lang/en/messages.php` (`failed`). Publish the translations with `--tag=forms-captcha-translations`.

## Forms sent with JavaScript

Captcha tokens can only be used once, so after a form is submitted the widget needs a new one. Forms Captcha watches `fetch()` and `XMLHttpRequest` (which axios uses), and resets a form's widget when a request to that form's `action` URL comes back, passed or failed. You don't need to do anything.

```js
form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
    });

    // The captcha has already been reset for the next try.
    const json = await response.json();
    if (!response.ok) showErrors(json.error); // json.error.captcha when the captcha failed
});
```

With reCAPTCHA v3 the token is fetched from Google as the form is submitted. Forms Captcha's submit listener runs before yours, holds the event until the token is in the form, then submits again, so your handler only ever sees a form with its token.

If you post somewhere other than the form's `action`, or add forms to the page after it loads, use the JavaScript API:

```js
FormsCaptcha.render(element); // render widgets added to the page
FormsCaptcha.reset(form);     // get a new token for a form's widget
```

Precognition requests (`$form.validate()` with Alpine) don't check the captcha, so they never use up the token. The captcha is checked when the form is submitted. Alpine's `$form.submit()` sends Alpine's data only, so send the form with `fetch()` and `new FormData(form)` instead, or copy the token into your Alpine data.

## Content Security Policy

The widget is a few attributes and a small inline script, which loads the provider's own script once. If you use Laravel's `Vite::useCspNonce()`, the script gets the nonce. Allow your provider's domains too:

- Turnstile: `https://challenges.cloudflare.com` (script-src, frame-src)
- hCaptcha: `https://hcaptcha.com`, `https://*.hcaptcha.com` (script-src, frame-src, style-src, connect-src)
- reCAPTCHA: `https://www.google.com/recaptcha/`, `https://www.gstatic.com/recaptcha/` (script-src, frame-src)

## Custom drivers

Add a provider in a service provider's `boot()` method:

```php
use NineteenNinetyFour\FormsCaptcha\Facades\Captcha;

Captcha::extend('friendly', fn ($app, array $config) => new FriendlyCaptcha($config));
```

Then set `FORMS_CAPTCHA_DRIVER=friendly`. A driver implements `NineteenNinetyFour\FormsCaptcha\Contracts\Captcha`:

```php
public function widget(array $options = []): string;                      // the HTML inside the form
public function verify(?string $token, ?string $ip = null): Verification;  // Verification::passed() or ::failed()
public function field(): string;                                          // the input the widget fills
public function configured(): bool;                                       // whether its keys are set
```

Providers that check tokens at a "siteverify" endpoint can extend `NineteenNinetyFour\FormsCaptcha\Drivers\SiteverifyCaptcha`.

## Coming from aryehraber/statamic-captcha

That addon checks a `{{ captcha }}` tag's token when `FormSubmitted` fires. Forms Captcha is a field instead, so the check is part of the form's validation and the error is on the field.

1. `composer require 1994/forms-captcha-statamic`.
2. Rename the `.env` keys: `CAPTCHA_SERVICE` to `FORMS_CAPTCHA_DRIVER` (`Recaptcha` → `recaptcha`, `Hcaptcha` → `hcaptcha`, `Turnstile` → `turnstile`), `CAPTCHA_SITEKEY` to `FORMS_CAPTCHA_SITE_KEY`, `CAPTCHA_SECRET` to `FORMS_CAPTCHA_SECRET_KEY`.
3. Add a Captcha field to each form that had `{{ captcha }}`, and replace `{{ captcha }}` in the templates with `{{ forms_captcha }}` (or nothing, if the template loops over `{{ fields }}`).
4. Remove the old addon: `composer remove aryehraber/statamic-captcha`, and its `config/captcha.php`.

## Support

Email [hello@1994.co.uk](mailto:hello@1994.co.uk), or open an issue on GitHub.
