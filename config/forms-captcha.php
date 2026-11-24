<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | The captcha every Captcha field uses: "turnstile", "hcaptcha",
    | "recaptcha" (v2, the "I'm not a robot" checkbox), "recaptcha_v3" or
    | "null". The null driver shows nothing and lets everything through,
    | for local development and tests. In .env, use "none" for it: env()
    | reads "null" as no value. No value at all is "turnstile", so a form
    | is never left open by a missing setting.
    |
    */

    'driver' => env('FORMS_CAPTCHA_DRIVER', 'turnstile'),

    /*
    |--------------------------------------------------------------------------
    | Keys
    |--------------------------------------------------------------------------
    |
    | From your provider's dashboard. Until both are set, Captcha fields show
    | a note in the page source instead of a widget, and every submission
    | with one fails, so a form is never left unprotected by mistake.
    |
    */

    'site_key' => env('FORMS_CAPTCHA_SITE_KEY'),

    'secret_key' => env('FORMS_CAPTCHA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA v3
    |--------------------------------------------------------------------------
    |
    | Google scores each visit from 0.0 (a bot) to 1.0 (a person). Visits
    | scoring under "score" are low scores. "low_scores" is what a new
    | Captcha field does with them, and each field can change it:
    | "reject" shows an error, "spam" keeps the submission as spam.
    |
    | Tokens made for another action are always refused. The action may
    | contain letters, numbers, slashes and underscores.
    |
    */

    'score' => env('FORMS_CAPTCHA_SCORE', 0.5),

    'low_scores' => 'reject',

    'action' => 'submit',

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for the provider to check a token. A provider that
    | can't be reached in time counts as a failed check.
    |
    */

    'timeout' => 5,

];
