# The demo

Dog-ear Books is a made-up bookshop with three forms, each with a Captcha field shown a different way. It runs locally in a few commands, on Cloudflare Turnstile's test keys, so it needs no account anywhere.

| Page | Shows |
| --- | --- |
| Contact | Statamic's own field rendering, with `{{ fields }}`. |
| Ask a bookseller | A hand-written template that places the widget with `{{ forms_captcha }}`. |
| The newsletter, in every page's footer | A form sent with `fetch()`: errors come back as JSON, and the widget resets for the next try. Turnstile's full-width size fits the narrow column. |
| Contact, in Blade | `<s:form:create>` with `<s:forms_captcha :fields="$fields" />`. |

## Running it

```bash
git clone https://github.com/1994limited/forms-captcha-statamic-demo.git
cd forms-captcha-statamic-demo
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan demo:users
php artisan serve
```

Sign in at `/cp` as `admin@example.com` with the password `password` (`demo:users` only runs with `APP_ENV=local`).

To see a submission refused, set `FORMS_CAPTCHA_SECRET_KEY` in `.env` to Turnstile's always-fail key, `2x0000000000000000000000000000000AA`. `.env.example` has the test keys for hCaptcha and reCAPTCHA v2 too.
