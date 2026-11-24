<?php

namespace NineteenNinetyFour\FormsCaptcha\Tags;

use Statamic\Tags\Tags;

/**
 * {{ forms_captcha }}: the form's Captcha field, for form templates that write their
 * own inputs instead of looping over {{ fields }}. Use it inside {{ form:create }}.
 */
class FormsCaptcha extends Tags
{
    protected static $handle = 'forms_captcha';

    public function index()
    {
        $fields = $this->params->get('fields', $this->context->get('fields'));

        $field = collect(is_iterable($fields) ? $fields : [])
            ->when($handle = $this->params->get('handle'), fn ($fields) => $fields->where('handle', $handle))
            ->first(fn ($field) => ($field['type'] ?? null) === 'captcha');

        return $field ? (string) $field['field'] : '';
    }
}
