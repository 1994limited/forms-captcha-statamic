<?php

namespace NineteenNinetyFour\FormsCaptcha\Listeners;

use NineteenNinetyFour\FormsCaptcha\Spam;
use Statamic\Events\FormSubmitted;

/**
 * Marks a submission as spam when its Captcha field let a low reCAPTCHA v3 score through.
 * Statamic then keeps it as spam (if the form stores submissions) and sends no emails.
 */
class MarkLowScoresAsSpam
{
    public function handle(FormSubmitted $event): void
    {
        $request = request();

        if (! Spam::flagged($request)) {
            return;
        }

        Spam::forget($request);

        $event->submission->markAsSpam();
    }
}
