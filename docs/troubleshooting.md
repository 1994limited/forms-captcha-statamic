# Troubleshooting

## The widget doesn't show

1. Is the Captcha field in the form, and on the page you're looking at (on multi-page forms)?
2. View the page source. `<!-- Forms Captcha: set FORMS_CAPTCHA_SITE_KEY and FORMS_CAPTCHA_SECRET_KEY to show the captcha. -->` means the keys aren't set: add them to `.env`, then `php artisan config:clear` if your config is cached.
3. Does your template output the field? With your own inputs, add `{{ forms_captcha }}` (in Blade, `<s:forms_captcha :fields="$fields" />`).
4. Check the browser's console. A Content Security Policy that doesn't allow the provider's domains blocks its script: see [Content Security Policy](configuration.md#content-security-policy).
5. The provider refuses widgets on domains its site key isn't set up for. Add the domain in the provider's dashboard, or use [test keys](installation.md#test-keys) locally.

## Every submission says "We couldn't check you're not a robot"

- The keys are missing, or the secret key doesn't belong to the site key. Without keys, every submission is refused on purpose.
- The server can't reach the provider (a firewall, or no outbound internet), or it took longer than `timeout` seconds.
- With reCAPTCHA v3, the `action` in the config file has changed since the page was loaded, or the keys are v2 keys.
- The form was sent twice with the same token. Tokens work once: see [JavaScript](javascript.md) for forms sent with `fetch()`.
- With Turnstile, the visitor sent the form before the widget had finished. Most widgets finish in a second or two.

## Submissions go through without a captcha

- `FORMS_CAPTCHA_DRIVER=none` turns checking off. It logs a warning in production.
- The form has no Captcha field. Captchas are per form: add the field to each form that needs one.

## Real people's submissions are marked as spam

That's reCAPTCHA v3 scoring them low. Lower `score` in `config/forms-captcha.php` (0.3, say), or set the field's **Low scores** to show an error instead, so people can try again. See [Low scores and spam](spam.md).

## A form sent with fetch() fails the second time

The widget resets when a request to the form's `action` URL comes back. If you post somewhere else, call `FormsCaptcha.reset(form)` after the response. See [JavaScript](javascript.md).
