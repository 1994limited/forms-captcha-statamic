# Coming from aryehraber/statamic-captcha

That addon checks a `{{ captcha }}` tag's token when `FormSubmitted` fires. Forms Captcha is a field instead, so the check is part of the form's validation and the error is on the field.

1. `composer require 1994/forms-captcha-statamic`.
2. Rename the `.env` keys: `CAPTCHA_SERVICE` to `FORMS_CAPTCHA_DRIVER` (`Recaptcha` → `recaptcha`, `Hcaptcha` → `hcaptcha`, `Turnstile` → `turnstile`), `CAPTCHA_SITEKEY` to `FORMS_CAPTCHA_SITE_KEY`, `CAPTCHA_SECRET` to `FORMS_CAPTCHA_SECRET_KEY`.
3. Add a Captcha field to each form that had `{{ captcha }}`, and replace `{{ captcha }}` in the templates with `{{ forms_captcha }}` (or nothing, if the template loops over `{{ fields }}`).
4. Remove the old addon: `composer remove aryehraber/statamic-captcha`, and its `config/captcha.php`.
