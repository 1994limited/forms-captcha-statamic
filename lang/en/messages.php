<?php

return [
    'failed' => "We couldn't check you're not a robot. Please try again.",
    'fieldtype_title' => 'Captcha',
    'fieldtype_description' => 'Stops bots with :provider, checked when the form is submitted.',
    'not_configured' => 'Set FORMS_CAPTCHA_SITE_KEY and FORMS_CAPTCHA_SECRET_KEY in .env to show the captcha. Until then, submissions are refused.',
    'theme' => 'Theme',
    'theme_instructions' => 'Auto follows the visitor\'s light or dark mode.',
    'theme_auto' => 'Auto',
    'theme_light' => 'Light',
    'theme_dark' => 'Dark',
    'size' => 'Size',
    'size_normal' => 'Normal',
    'size_compact' => 'Compact',
    'low_scores' => 'Low scores',
    'low_scores_instructions' => 'What happens when reCAPTCHA thinks a visitor is probably a bot. Kept as spam, the submission is saved for review (if the form stores submissions) and no emails are sent.',
    'low_scores_reject' => 'Show an error',
    'low_scores_spam' => 'Keep as spam',
    'providers' => [
        'turnstile' => 'Cloudflare Turnstile',
        'hcaptcha' => 'hCaptcha',
        'recaptcha' => 'reCAPTCHA v2',
        'recaptcha_v3' => 'reCAPTCHA v3',
        'null' => 'no provider (the null driver lets everything through)',
    ],
];
