# Changelog

## Unreleased

### Added
- A Captcha form fieldtype for Statamic 6.44's forms builder, checked on the server as a validation rule and never stored.
- Cloudflare Turnstile, hCaptcha, reCAPTCHA v2, reCAPTCHA v3 and null drivers, set in `config/forms-captcha.php`, and `Captcha::extend()` for others.
- reCAPTCHA v3 low scores kept as spam, per field.
- Theme and size per field, for the widgets you can see.
- `{{ forms_captcha }}` for form templates with their own inputs.
- Widgets reset after a form is posted with `fetch()` or `XMLHttpRequest`; reCAPTCHA v3 gets its token before the site's own submit handlers run.
