<?php

namespace NineteenNinetyFour\FormsCaptcha\FormFieldtypes;

use Illuminate\Support\HtmlString;
use NineteenNinetyFour\FormsCaptcha\CaptchaManager;
use NineteenNinetyFour\FormsCaptcha\Drivers\RecaptchaV3;
use Statamic\Forms\Fields\FormFieldtype;
use Statamic\Support\Arr;

use function Statamic\trans as __;

/**
 * The Captcha field in the forms builder.
 */
class CaptchaFormFieldtype extends FormFieldtype
{
    protected static $fieldtype = 'captcha';

    protected $icon = 'security-lock';

    protected $categories = ['structure'];

    protected $keywords = ['captcha', 'spam', 'bot', 'robot', 'turnstile', 'hcaptcha', 'recaptcha', 'cloudflare'];

    protected $collectsValue = false;

    public function title(): string
    {
        return __('forms-captcha::messages.fieldtype_title');
    }

    public function description(): ?string
    {
        $provider = __('forms-captcha::messages.providers.'.$this->driverHandle());

        $description = __('forms-captcha::messages.fieldtype_description', [
            'provider' => str_starts_with($provider, 'forms-captcha::') ? $this->driverHandle() : $provider,
        ]);

        return $this->captcha()->configured() ? $description : $description.' '.__('forms-captcha::messages.not_configured');
    }

    protected function configFieldItems(): array
    {
        $fields = [
            'display' => [
                'display' => __('Label'),
                'type' => 'text',
                'default' => __('forms-captcha::messages.fieldtype_title'),
                'validate' => 'required',
            ],
            'instructions' => ['type' => 'hidden'],
        ];

        if ($this->captcha() instanceof RecaptchaV3) {
            return $fields + [
                'low_scores' => [
                    'display' => __('forms-captcha::messages.low_scores'),
                    'instructions' => __('forms-captcha::messages.low_scores_instructions'),
                    'type' => 'button_group',
                    'default' => config('forms-captcha.low_scores') === 'spam' ? 'spam' : 'reject',
                    'options' => [
                        'reject' => __('forms-captcha::messages.low_scores_reject'),
                        'spam' => __('forms-captcha::messages.low_scores_spam'),
                    ],
                ],
            ];
        }

        if (in_array($this->driverHandle(), ['turnstile', 'hcaptcha', 'recaptcha'], true)) {
            return $fields + [
                'theme' => [
                    'display' => __('forms-captcha::messages.theme'),
                    'instructions' => __('forms-captcha::messages.theme_instructions'),
                    'type' => 'button_group',
                    'default' => 'auto',
                    'options' => [
                        'auto' => __('forms-captcha::messages.theme_auto'),
                        'light' => __('forms-captcha::messages.theme_light'),
                        'dark' => __('forms-captcha::messages.theme_dark'),
                    ],
                ],
                'size' => [
                    'display' => __('forms-captcha::messages.size'),
                    'type' => 'button_group',
                    'default' => 'normal',
                    'options' => [
                        'normal' => __('forms-captcha::messages.size_normal'),
                        'compact' => __('forms-captcha::messages.size_compact'),
                    ],
                ],
            ];
        }

        return $fields;
    }

    public function toFieldArray(): array
    {
        return [
            'type' => 'captcha',
            'listable' => false,
            ...Arr::except($this->config(), ['type', 'listable']),
        ];
    }

    public function view(): string
    {
        return 'forms-captcha::field';
    }

    public function prepareFrontEndRenderable(array $data): array
    {
        $captcha = $this->captcha();

        return array_merge($data, [
            // Rendered on its own, without a label: the widget explains itself, and reCAPTCHA v3 has nothing to see.
            'is_informative' => true,
            'captcha' => new HtmlString($captcha->widget([
                'theme' => $this->config('theme'),
                'size' => $this->config('size'),
            ])),
            'captcha_driver' => $this->driverHandle(),
            'captcha_input' => $captcha->field(),
        ]);
    }

    protected function captcha()
    {
        return app(CaptchaManager::class)->driver();
    }

    protected function driverHandle(): string
    {
        return app(CaptchaManager::class)->getDefaultDriver();
    }
}
