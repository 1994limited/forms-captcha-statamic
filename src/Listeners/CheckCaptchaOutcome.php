<?php

namespace NineteenNinetyFour\FormsCaptcha\Listeners;

use Illuminate\Validation\ValidationException;
use NineteenNinetyFour\FormsCaptcha\Outcome;
use Statamic\Events\FormSubmitted;

use function Statamic\trans as __;

/**
 * Before a submission with a Captcha field is finalised, makes sure the field passed, on
 * whichever page it's on, and marks the submission as spam when it let a low reCAPTCHA v3
 * score through. Statamic then keeps it as spam (if the form stores submissions) and sends
 * no emails or connections.
 */
class CheckCaptchaOutcome
{
    public function handle(FormSubmitted $event): void
    {
        $submission = $event->submission;

        if (! $form = $submission->form()) {
            return;
        }

        $handle = $form->blueprint()->fields()->all()->first(fn ($field) => $field->type() === 'captcha')?->handle();

        if (! $handle) {
            return;
        }

        $outcome = Outcome::pull(request(), $form);

        if ($outcome === Outcome::SPAM) {
            $submission->markAsSpam();

            return;
        }

        if ($outcome !== Outcome::PASSED) {
            // The Captcha field's page was never submitted: on a multi-page form, posting a
            // later page straight away would otherwise skip it.
            throw ValidationException::withMessages([$handle => __('forms-captcha::messages.failed')]);
        }
    }
}
