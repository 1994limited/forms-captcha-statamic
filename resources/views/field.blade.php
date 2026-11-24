{{--
    A Captcha field. Publish with `php artisan vendor:publish --tag=forms-captcha-views`
    to change it: $captcha is the widget, $error the message when the check failed.
--}}
<div class="forms-captcha" id="{{ $id }}" @if ($error ?? false) role="group" aria-describedby="{{ $id }}-error" @endif>
    {!! $captcha !!}
    @if ($error ?? false)
        <p class="forms-captcha-error" id="{{ $id }}-error">{{ $error }}</p>
    @endif
</div>
