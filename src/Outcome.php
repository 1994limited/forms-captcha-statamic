<?php

namespace NineteenNinetyFour\FormsCaptcha;

use Illuminate\Http\Request;
use Statamic\Contracts\Forms\Form;

/**
 * Whether a form's Captcha field passed, kept until the submission is finalised.
 *
 * On a single-page form that's the same request, so it's kept on the request. On a
 * multi-page form the field may be on an earlier page than the one that finalises
 * the submission, so it's kept in the session too, against the form.
 */
class Outcome
{
    const PASSED = 'passed';

    const SPAM = 'spam';

    const KEY = 'forms-captcha.outcome';

    public static function record(Request $request, string $outcome): void
    {
        $request->attributes->set(static::KEY, $outcome);

        $form = $request->route('form');

        if ($form instanceof Form && $form->hasMultiplePages() && $request->hasSession()) {
            $request->session()->put(static::KEY.'.'.$form->handle(), $outcome);
        }
    }

    /**
     * The outcome for the form, forgotten once read so it's only used for one submission.
     */
    public static function pull(Request $request, Form $form): ?string
    {
        $outcome = $request->attributes->get(static::KEY);
        $request->attributes->remove(static::KEY);

        $stored = $request->hasSession() ? $request->session()->pull(static::KEY.'.'.$form->handle()) : null;

        return $outcome ?? $stored;
    }
}
