<?php

namespace NineteenNinetyFour\FormsCaptcha\Listeners;

use Statamic\Events\SubmissionSaving;

/**
 * Captcha fields never have a value, so they're left out of saved submissions
 * instead of being saved as empty.
 */
class ForgetCaptchaFields
{
    public function handle(SubmissionSaving $event): void
    {
        $submission = $event->submission;

        $submission->form()?->blueprint()->fields()->all()
            ->filter(fn ($field) => $field->type() === 'captcha')
            ->keys()
            ->each(fn ($handle) => $submission->remove($handle));
    }
}
