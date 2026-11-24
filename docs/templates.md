# Templates

The Captcha field works with Statamic's own field rendering and with templates that write their own inputs. Every example has an Antlers and a Blade version.

## Statamic's field rendering

Nothing to do. The Captcha field renders with the rest of the form's fields:

```antlers
{{ form:contact }}
    {{ fields }}
        {{ if is_informative }}
            {{ field }}
        {{ else }}
            <label for="{{ id }}">{{ display }}</label>
            {{ field }}
            {{ if error }}<p>{{ error }}</p>{{ /if }}
        {{ /if }}
    {{ /fields }}
    <button>Send</button>
{{ /form:contact }}
```

<details>
<summary>Blade</summary>

```blade
<s:form:create in="contact">
    @foreach ($fields as $field)
        @if ($field['is_informative'])
            {!! $field['field'] !!}
        @else
            <label for="{{ $field['id'] }}">{{ $field['display'] }}</label>
            {!! $field['field'] !!}
            @if ($field['error']) <p>{{ $field['error'] }}</p> @endif
        @endif
    @endforeach
    <button>Send</button>
</s:form:create>
```

</details>

The Captcha field is `is_informative`, like a heading, so it's shown without a label. Its `{{ field }}` includes its own error message under the widget.

## Your own form template

Place the widget with `{{ forms_captcha }}` inside the form tag. Use `handle="…"` if a form has more than one. It includes its own error message, so there's no need to print `{{ error:captcha }}` as well (though you can, and leave it out of `{{ errors }}` lists).

```antlers
{{ form:contact }}
    <label>Email <input type="email" name="email" value="{{ old:email }}"></label>
    {{ forms_captcha }}
    <button>Send</button>
{{ /form:contact }}
```

<details>
<summary>Blade</summary>

```blade
<s:form:create in="contact">
    <label>Email <input type="email" name="email" value="{{ old('email') }}"></label>
    <s:forms_captcha :fields="$fields" />
    <button>Send</button>
</s:form:create>
```

In Blade the tag can't see the form's fields by itself, so pass them with `:fields="$fields"`.

</details>

The widget posts its token under the provider's own name (`cf-turnstile-response`, `h-captcha-response` or `g-recaptcha-response`). If you render the provider's widget yourself, post the token under one of those, or under the Captcha field's handle.

## Changing the markup

The field is rendered by one Blade view, whatever your templates use. Publish it to change it:

```bash
php artisan vendor:publish --tag=forms-captcha-views
```

It's then at `resources/views/vendor/forms-captcha/field.blade.php`. `$captcha` is the widget, `$error` the message, `$id` the field's id.

The error message is in `lang/en/messages.php` (`failed`). Publish the translations with `--tag=forms-captcha-translations`.
