<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests;

use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use NineteenNinetyFour\FormsCaptcha\CaptchaManager;
use NineteenNinetyFour\FormsCaptcha\ServiceProvider;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Facades\Antlers;
use Statamic\Facades\Form;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    const TURNSTILE = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    const RECAPTCHA = 'https://www.google.com/recaptcha/api/siteverify';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('view.paths', [__DIR__.'/__fixtures__/views']);
        $app['config']->set('forms-captcha.driver', 'turnstile');
        $app['config']->set('forms-captcha.site_key', 'site-key');
        $app['config']->set('forms-captcha.secret_key', 'secret-key');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // More than one form, and multi-page forms, need Forms Pro.
        Composer::shouldReceive('isInstalled')->andReturnFalse()->byDefault();
        Composer::shouldReceive('isInstalled')->with('statamic/forms-pro')->andReturnTrue()->byDefault();
    }

    protected function tearDown(): void
    {
        Form::all()->each(fn ($form) => $form->submissions()->each->delete());

        parent::tearDown();
    }

    /**
     * Use another driver or settings, as config/forms-captcha.php would.
     */
    protected function captchaConfig(array $config): void
    {
        config(collect($config)->mapWithKeys(fn ($value, $key) => ["forms-captcha.{$key}" => $value])->all());

        $this->app->forgetInstance(CaptchaManager::class);
    }

    /**
     * A contact form with an email field and a Captcha field.
     */
    protected function contactForm(array $captcha = [], array $data = [], string $handle = 'contact'): FormContract
    {
        return tap(Form::make($handle)->title('Contact')->store(true)->honeypot('winnie')->data($data)->formFields([
            'pages' => [
                [
                    'id' => 'main',
                    'sections' => [
                        ['fields' => [
                            ['handle' => 'email', 'field' => ['type' => 'email', 'display' => 'Email', 'validate' => ['required']]],
                            ['handle' => 'captcha', 'field' => ['type' => 'captcha', 'display' => 'Captcha', ...$captcha]],
                        ]],
                    ],
                ],
            ],
        ]))->save();
    }

    protected function provider(string $url, array $response, int $status = 200): void
    {
        Http::fake([$url => Http::response($response, $status)]);
    }

    protected function assertProviderSent(string $token): void
    {
        Http::assertSent(fn (Request $request) => $request['response'] === $token && $request['secret'] === 'secret-key');
    }

    /**
     * Render Antlers as a site template would (trusted, so tags run).
     */
    protected function antlers(string $template, array $data = []): string
    {
        return (string) Antlers::parse($template, $data, true);
    }
}
