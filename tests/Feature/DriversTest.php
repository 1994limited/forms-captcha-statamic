<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use NineteenNinetyFour\FormsCaptcha\Drivers\Hcaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\NullCaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\Recaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\RecaptchaV3;
use NineteenNinetyFour\FormsCaptcha\Drivers\SiteverifyCaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\Turnstile;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DriversTest extends TestCase
{
    private const CONFIG = ['site_key' => 'site-key', 'secret_key' => 'secret-key'];

    public static function drivers(): array
    {
        return [
            'turnstile' => [Turnstile::class, 'https://challenges.cloudflare.com/turnstile/v0/siteverify', 'cf-turnstile-response', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'],
            'hcaptcha' => [Hcaptcha::class, 'https://api.hcaptcha.com/siteverify', 'h-captcha-response', 'https://js.hcaptcha.com/1/api.js?render=explicit'],
            'recaptcha' => [Recaptcha::class, 'https://www.google.com/recaptcha/api/siteverify', 'g-recaptcha-response', 'https://www.google.com/recaptcha/api.js?render=explicit'],
        ];
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_checks_a_token_with_the_provider($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response(['success' => true])]);

        $captcha = new $class(self::CONFIG);

        $this->assertTrue($captcha->verify('the-token', '127.0.0.1')->passed);
        $this->assertSame($field, $captcha->field());

        Http::assertSent(fn (Request $request) => $request->url() === $endpoint
            && $request->method() === 'POST'
            && $request->isForm()
            && $request['secret'] === 'secret-key'
            && $request['response'] === 'the-token'
            && $request['remoteip'] === '127.0.0.1');
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_when_the_provider_says_so($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response(['success' => false, 'error-codes' => ['invalid-input-response', 'timeout-or-duplicate']])]);

        $verification = (new $class(self::CONFIG))->verify('the-token');

        $this->assertFalse($verification->passed);
        $this->assertSame(['invalid-input-response', 'timeout-or-duplicate'], $verification->errors);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_when_success_isnt_exactly_true($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response(['success' => 'true'])]);

        $this->assertFalse((new $class(self::CONFIG))->verify('the-token')->passed);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_when_the_provider_errors($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response(['success' => true], 500)]);

        $this->assertSame(['unreachable'], (new $class(self::CONFIG))->verify('the-token')->errors);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_when_the_provider_returns_something_other_than_json($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response('<html>nope</html>')]);

        $this->assertFalse((new $class(self::CONFIG))->verify('the-token')->passed);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_when_the_provider_cant_be_reached($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => fn () => throw new ConnectionException('Timed out')]);

        $this->assertSame(['unreachable'], (new $class(self::CONFIG))->verify('the-token')->errors);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_without_asking_when_there_is_no_token($class, $endpoint, $field, $script)
    {
        Http::fake();

        $captcha = new $class(self::CONFIG);

        $this->assertSame(['missing-input-response'], $captcha->verify(null)->errors);
        $this->assertSame(['missing-input-response'], $captcha->verify('')->errors);

        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_without_asking_when_the_token_is_too_long($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response(['success' => true])]);

        $captcha = new $class(self::CONFIG);

        $this->assertFalse($captcha->verify(str_repeat('a', 8193))->passed);
        Http::assertNothingSent();

        $this->assertTrue($captcha->verify(str_repeat('a', 8192))->passed);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_fails_without_asking_when_the_keys_arent_set($class, $endpoint, $field, $script)
    {
        Http::fake();

        $captcha = new $class(['site_key' => 'site-key']);

        $this->assertFalse($captcha->configured());
        $this->assertSame(['not-configured'], $captcha->verify('the-token')->errors);
        $this->assertStringContainsString('<!-- Forms Captcha: set FORMS_CAPTCHA_SITE_KEY and FORMS_CAPTCHA_SECRET_KEY', $captcha->widget());
        $this->assertStringNotContainsString('data-forms-captcha', $captcha->widget());

        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_leaves_out_the_ip_when_there_isnt_one($class, $endpoint, $field, $script)
    {
        Http::fake([$endpoint => Http::response(['success' => true])]);

        (new $class(self::CONFIG))->verify('the-token');

        Http::assertSent(fn (Request $request) => ! array_key_exists('remoteip', $request->data()));
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_renders_a_widget_for_the_script_to_fill($class, $endpoint, $field, $script)
    {
        $widget = (new $class(self::CONFIG))->widget();
        $handle = (new $class(self::CONFIG))->handle();

        $this->assertStringStartsWith(
            '<div data-forms-captcha="'.$handle.'" data-sitekey="site-key" data-src="'.htmlspecialchars($script).'"></div><script>',
            $widget,
        );
        $this->assertStringEndsWith('</script>', $widget);
    }

    #[Test]
    #[DataProvider('drivers')]
    public function it_passes_the_theme_and_size_on($class, $endpoint, $field, $script)
    {
        $widget = (new $class(self::CONFIG))->widget(['theme' => 'dark', 'size' => 'compact']);

        $this->assertStringContainsString('data-theme="dark" data-size="compact"', $widget);

        $widget = (new $class(self::CONFIG))->widget(['theme' => 'auto', 'size' => 'normal']);

        $this->assertStringNotContainsString('data-theme', $widget);
        $this->assertStringNotContainsString('data-size', $widget);

        $widget = (new $class(self::CONFIG))->widget(['theme' => '" onmouseover="alert(1)']);

        $this->assertStringNotContainsString('onmouseover', $widget);
    }

    #[Test]
    public function hcaptcha_sends_the_site_key()
    {
        Http::fake(['https://api.hcaptcha.com/siteverify' => Http::response(['success' => true])]);

        (new Hcaptcha(self::CONFIG))->verify('the-token');

        Http::assertSent(fn (Request $request) => $request['sitekey'] === 'site-key');
    }

    #[Test]
    public function it_escapes_the_site_key_in_the_widget()
    {
        $widget = (new Turnstile([...self::CONFIG, 'site_key' => '"><script>alert(1)</script>']))->widget();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $widget);
        $this->assertStringContainsString('data-sitekey="&quot;&gt;&lt;script&gt;', $widget);
    }

    #[Test]
    public function the_loader_script_runs_once_and_has_no_less_than_signs()
    {
        // Statamic tidies field HTML with a regex that would match from a "<" to the next ">".
        $this->assertStringNotContainsString('<', SiteverifyCaptcha::loaderScript());
        $this->assertStringStartsWith('(function () {'."\n".'if (window.FormsCaptcha) {', SiteverifyCaptcha::loaderScript());
        $this->assertStringNotContainsString('// ', SiteverifyCaptcha::loaderScript());
    }

    #[Test]
    public function recaptcha_v3_passes_with_a_good_enough_score_for_the_right_action()
    {
        Http::fake([self::RECAPTCHA => Http::response(['success' => true, 'score' => 0.7, 'action' => 'submit'])]);

        $verification = (new RecaptchaV3(self::CONFIG))->verify('the-token');

        $this->assertTrue($verification->passed);
        $this->assertSame(0.7, $verification->score);
    }

    #[Test]
    public function recaptcha_v3_reports_a_low_score()
    {
        Http::fake([self::RECAPTCHA => Http::response(['success' => true, 'score' => 0.3, 'action' => 'submit'])]);

        $verification = (new RecaptchaV3(self::CONFIG))->verify('the-token');

        $this->assertFalse($verification->passed);
        $this->assertTrue($verification->lowScore);
        $this->assertSame(0.3, $verification->score);
    }

    #[Test]
    public function recaptcha_v3_uses_the_configured_score()
    {
        Http::fake([self::RECAPTCHA => Http::response(['success' => true, 'score' => 0.3, 'action' => 'submit'])]);

        $this->assertTrue((new RecaptchaV3([...self::CONFIG, 'score' => 0.2]))->verify('the-token')->passed);
        $this->assertFalse((new RecaptchaV3([...self::CONFIG, 'score' => 0.9]))->verify('the-token')->passed);
        $this->assertTrue((new RecaptchaV3([...self::CONFIG, 'score' => '0.3']))->verify('the-token')->passed);
    }

    #[Test]
    public function recaptcha_v3_fails_without_a_score()
    {
        Http::fake([self::RECAPTCHA => Http::response(['success' => true, 'action' => 'submit'])]);

        $verification = (new RecaptchaV3(self::CONFIG))->verify('the-token');

        $this->assertFalse($verification->passed);
        $this->assertFalse($verification->lowScore);
    }

    #[Test]
    public function recaptcha_v3_fails_for_a_token_made_for_another_action()
    {
        Http::fake([self::RECAPTCHA => Http::response(['success' => true, 'score' => 0.9, 'action' => 'login'])]);

        $verification = (new RecaptchaV3(self::CONFIG))->verify('the-token');

        $this->assertFalse($verification->passed);
        $this->assertFalse($verification->lowScore);
        $this->assertSame(['action-mismatch'], $verification->errors);
        $this->assertTrue((new RecaptchaV3([...self::CONFIG, 'action' => 'login']))->verify('the-token')->passed);
    }

    #[Test]
    public function recaptcha_v3_falls_back_to_the_default_action_when_the_configured_one_is_invalid()
    {
        $this->assertSame('submit', (new RecaptchaV3([...self::CONFIG, 'action' => 'not allowed!']))->action());
        $this->assertSame('contact/enquiry', (new RecaptchaV3([...self::CONFIG, 'action' => 'contact/enquiry']))->action());
    }

    #[Test]
    public function recaptcha_v3_renders_a_hidden_input_for_the_token()
    {
        $widget = (new RecaptchaV3([...self::CONFIG, 'action' => 'contact']))->widget(['theme' => 'dark']);

        $this->assertStringStartsWith(
            '<div data-forms-captcha="recaptcha_v3" data-sitekey="site-key" data-src="https://www.google.com/recaptcha/api.js?render=site-key" data-action="contact"><input type="hidden" name="g-recaptcha-response" value=""></div><script>',
            $widget,
        );
    }

    #[Test]
    public function recaptcha_v3_keeps_the_site_key_out_of_the_scripts()
    {
        $widget = (new RecaptchaV3([...self::CONFIG, 'site_key' => "</script><script>alert('x')</script>"]))->widget();

        $this->assertStringNotContainsString("<script>alert('x')</script>", $widget);
        $this->assertSame(1, substr_count($widget, '</script>'));
        $this->assertStringContainsString('api.js?render=%3C%2Fscript%3E', $widget);
    }

    #[Test]
    public function recaptcha_v3_waits_for_a_token_before_the_form_is_submitted()
    {
        $script = SiteverifyCaptcha::loaderScript();

        // Before the site's own submit handlers, so an AJAX handler only sees a form with its token.
        $this->assertStringContainsString("document.addEventListener(\n'submit',", $script);
        $this->assertStringContainsString('event.stopImmediatePropagation();', $script);
        $this->assertStringContainsString('api.execute(el.dataset.sitekey, { action: el.dataset.action })', $script);
        $this->assertStringContainsString('provider.ready ? api.ready(done) : done();', $script);
    }

    #[Test]
    public function the_null_driver_shows_nothing_and_lets_everything_through()
    {
        $captcha = new NullCaptcha;

        $this->assertSame('', $captcha->widget());
        $this->assertTrue($captcha->verify(null)->passed);
        $this->assertTrue($captcha->configured());
    }

    #[Test]
    public function the_script_carries_the_csp_nonce()
    {
        Vite::useCspNonce('abc123');

        $this->assertStringContainsString('<script nonce="abc123">', (new Turnstile(self::CONFIG))->widget());
    }
}
