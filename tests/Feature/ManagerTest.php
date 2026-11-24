<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use NineteenNinetyFour\FormsCaptcha\Contracts\Captcha as CaptchaContract;
use NineteenNinetyFour\FormsCaptcha\Drivers\NullCaptcha;
use NineteenNinetyFour\FormsCaptcha\Drivers\RecaptchaV3;
use NineteenNinetyFour\FormsCaptcha\Drivers\Turnstile;
use NineteenNinetyFour\FormsCaptcha\Facades\Captcha;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use NineteenNinetyFour\FormsCaptcha\Verification;
use PHPUnit\Framework\Attributes\Test;

class ManagerTest extends TestCase
{
    #[Test]
    public function it_uses_the_configured_driver_and_its_settings()
    {
        $this->assertInstanceOf(Turnstile::class, Captcha::driver());

        $this->captchaConfig(['driver' => 'recaptcha_v3', 'score' => 0.7, 'action' => 'contact']);

        $driver = Captcha::driver();
        $this->assertInstanceOf(RecaptchaV3::class, $driver);
        $this->assertSame(0.7, $driver->score());
        $this->assertSame('contact', $driver->action());
    }

    #[Test]
    public function a_blank_driver_is_turnstile_so_forms_stay_protected()
    {
        foreach ([null, ''] as $driver) {
            $this->captchaConfig(['driver' => $driver]);

            $this->assertInstanceOf(Turnstile::class, Captcha::driver());
        }
    }

    #[Test]
    public function none_and_null_are_the_null_driver()
    {
        foreach (['none', 'null'] as $driver) {
            $this->captchaConfig(['driver' => $driver]);

            $this->assertInstanceOf(NullCaptcha::class, Captcha::driver());
        }
    }

    #[Test]
    public function the_null_driver_warns_in_production()
    {
        Log::spy();
        $this->app['env'] = 'production';

        (new NullCaptcha)->verify(null);

        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function an_unknown_driver_says_so()
    {
        $this->captchaConfig(['driver' => 'clippy']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Captcha driver [clippy] is not defined.');

        Captcha::driver();
    }

    #[Test]
    public function a_custom_driver_can_be_added()
    {
        $this->captchaConfig(['driver' => 'friendly', 'site_key' => 'fc-key']);

        Captcha::extend('friendly', fn ($app, array $config) => new class($config) implements CaptchaContract
        {
            public function __construct(private array $config) {}

            public function widget(array $options = []): string
            {
                return '<div class="frc-captcha" data-sitekey="'.$this->config['site_key'].'"></div>';
            }

            public function verify(?string $token, ?string $ip = null): Verification
            {
                return $token === 'friendly-token' ? Verification::passed() : Verification::failed('nope');
            }

            public function field(): string
            {
                return 'frc-captcha-response';
            }

            public function configured(): bool
            {
                return true;
            }
        });

        $form = $this->contactForm();

        $this->assertStringContainsString('<div class="frc-captcha" data-sitekey="fc-key"></div>', $this->antlers('{{ form:contact }}{{ forms_captcha }}{{ /form:contact }}'));

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'frc-captcha-response' => 'wrong'])->assertSessionHasErrorsIn('form.contact', ['captcha']);
        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'frc-captcha-response' => 'friendly-token'])->assertSessionHasNoErrors();
    }

    #[Test]
    public function a_token_is_only_sent_to_the_provider_once_a_request()
    {
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->assertTrue(Captcha::verify('the-token')->passed);
        $this->assertTrue(Captcha::verify('the-token')->passed);
        $this->assertFalse(Captcha::verify(null)->passed);

        Http::assertSentCount(1);
    }
}
