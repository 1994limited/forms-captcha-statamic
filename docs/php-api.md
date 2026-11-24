# PHP API

## Checking a token yourself

The configured provider is available for your own forms and controllers, outside Statamic's forms:

```php
use NineteenNinetyFour\FormsCaptcha\Facades\Captcha;

$captcha = Captcha::driver();

$html = $captcha->widget();                     // the widget, for your own form
$token = $request->input($captcha->field());     // cf-turnstile-response, for example

$verification = Captcha::verify($token, $request->ip());

$verification->passed;    // bool
$verification->lowScore;  // reCAPTCHA v3: a valid token that scored under the threshold
$verification->score;     // reCAPTCHA v3 only
$verification->errors;    // the provider's error codes, or missing-input-response, not-configured, unreachable
```

`Captcha::verify()` remembers each token's answer for the rest of the request, since providers only accept a token once.

## Custom drivers

Add a provider in a service provider's `boot()` method:

```php
use NineteenNinetyFour\FormsCaptcha\Facades\Captcha;

Captcha::extend('friendly', fn ($app, array $config) => new FriendlyCaptcha($config));
```

Then set `FORMS_CAPTCHA_DRIVER=friendly`. A driver implements `NineteenNinetyFour\FormsCaptcha\Contracts\Captcha`:

```php
public function widget(array $options = []): string;                      // the HTML inside the form
public function verify(?string $token, ?string $ip = null): Verification;  // Verification::passed() or ::failed()
public function field(): string;                                          // the input the widget fills
public function configured(): bool;                                       // whether its keys are set
```

Providers that check tokens at a "siteverify" endpoint can extend `NineteenNinetyFour\FormsCaptcha\Drivers\SiteverifyCaptcha`.
