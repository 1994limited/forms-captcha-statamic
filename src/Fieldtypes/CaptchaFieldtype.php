<?php

namespace NineteenNinetyFour\FormsCaptcha\Fieldtypes;

use NineteenNinetyFour\FormsCaptcha\Rules\PassesCaptcha;
use Statamic\Fields\Fieldtype;

/**
 * The fieldtype a Captcha form field becomes in the form's blueprint. It checks the
 * token as a validation rule and never stores it. Only forms can use it.
 */
class CaptchaFieldtype extends Fieldtype
{
    protected static $handle = 'captcha';

    protected $selectable = false;

    protected $icon = 'security-lock';

    public function rules(): array
    {
        return [new PassesCaptcha($this->lowScores())];
    }

    public function process($data)
    {
        return null;
    }

    public function preProcess($data)
    {
        return null;
    }

    public function augment($value)
    {
        return null;
    }

    public function lowScores(): string
    {
        $value = $this->config('low_scores') ?: config('forms-captcha.low_scores', 'reject');

        return $value === 'spam' ? 'spam' : 'reject';
    }
}
