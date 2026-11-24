<?php

namespace NineteenNinetyFour\FormsCaptcha;

use NineteenNinetyFour\FormsCaptcha\Fieldtypes\CaptchaFieldtype;
use NineteenNinetyFour\FormsCaptcha\FormFieldtypes\CaptchaFormFieldtype;
use NineteenNinetyFour\FormsCaptcha\Listeners\CheckCaptchaOutcome;
use NineteenNinetyFour\FormsCaptcha\Listeners\ForgetCaptchaFields;
use NineteenNinetyFour\FormsCaptcha\Tags\FormsCaptcha;
use Statamic\Events\FormSubmitted;
use Statamic\Events\SubmissionSaving;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [CaptchaFieldtype::class];

    protected $formFieldtypes = [CaptchaFormFieldtype::class];

    protected $tags = [FormsCaptcha::class];

    protected $listen = [
        FormSubmitted::class => [CheckCaptchaOutcome::class],
        SubmissionSaving::class => [ForgetCaptchaFields::class],
    ];

    protected $config = false;

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/forms-captcha.php', 'forms-captcha');

        $this->app->singleton(CaptchaManager::class, fn ($app) => new CaptchaManager($app));
    }

    public function bootAddon(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forms-captcha');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'forms-captcha');

        $this->publishes([__DIR__.'/../config/forms-captcha.php' => config_path('forms-captcha.php')], 'forms-captcha-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/forms-captcha')], 'forms-captcha-views');
        $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/forms-captcha')], 'forms-captcha-translations');
    }
}
