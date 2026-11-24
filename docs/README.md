# Forms Captcha documentation

Forms Captcha adds a Captcha field to Statamic's forms: Cloudflare Turnstile, hCaptcha or Google reCAPTCHA, checked on the server when the form is submitted. Every template example has an Antlers and a Blade version.

## Setting up

1. [Installation](installation.md): requirements, installing, your provider's keys, test keys, updating.
2. [Configuration](configuration.md): `config/forms-captcha.php`, each field's settings, Content Security Policy.

## On your site

3. [Templates](templates.md): Statamic's field rendering, your own form template, Blade, changing the markup.
4. [JavaScript](javascript.md): forms sent with `fetch()` or axios, reCAPTCHA v3, the JavaScript API, Precognition.
5. [Low scores and spam](spam.md): reCAPTCHA v3 scores, keeping them as spam, multi-page forms.

## Reference

6. [PHP API](php-api.md): checking a token yourself, and custom drivers.
7. [Coming from aryehraber/statamic-captcha](migrating.md): moving a site over.
8. [Troubleshooting](troubleshooting.md): common problems and fixes.
