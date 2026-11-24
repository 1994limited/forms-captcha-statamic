<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use NineteenNinetyFour\FormsCaptcha\Contracts\Captcha;
use NineteenNinetyFour\FormsCaptcha\Verification;
use Throwable;

/**
 * A captcha checked by posting its token to the provider's "siteverify"
 * endpoint, which is how Turnstile, hCaptcha and reCAPTCHA all work.
 */
abstract class SiteverifyCaptcha implements Captcha
{
    /**
     * Providers cap their tokens well below this, so anything longer isn't
     * worth sending.
     */
    const MAX_TOKEN_LENGTH = 8192;

    public function __construct(protected array $config = [])
    {
        //
    }

    /**
     * The handle the front-end script knows the provider by. The script loads each
     * provider's own script from a URL of its own, never one from the page.
     */
    abstract public function handle(): string;

    abstract protected function endpoint(): string;

    public function configured(): bool
    {
        return filled($this->siteKey()) && filled($this->secretKey());
    }

    public function widget(array $options = []): string
    {
        if (! $this->configured()) {
            return '<!-- Forms Captcha: set FORMS_CAPTCHA_SITE_KEY and FORMS_CAPTCHA_SECRET_KEY to show the captcha. -->';
        }

        $attributes = array_filter([
            'data-forms-captcha' => $this->handle(),
            'data-sitekey' => $this->siteKey(),
            ...$this->widgetAttributes($options),
        ], fn ($value) => $value !== null && $value !== '');

        $html = collect($attributes)->map(fn ($value, $name) => $name.'="'.$this->attr($value).'"')->implode(' ');

        return '<div '.$html.'>'.$this->widgetContents().'</div>'.static::loader();
    }

    /**
     * Extra data attributes for the widget's element.
     */
    protected function widgetAttributes(array $options): array
    {
        return [
            'data-theme' => in_array($options['theme'] ?? null, ['light', 'dark'], true) ? $options['theme'] : null,
            'data-size' => ($options['size'] ?? null) === 'compact' ? 'compact' : null,
        ];
    }

    /**
     * What goes inside the widget's element before the provider fills it.
     */
    protected function widgetContents(): string
    {
        return '';
    }

    public function verify(?string $token, ?string $ip = null): Verification
    {
        if ($token === null || $token === '' || strlen($token) > static::MAX_TOKEN_LENGTH) {
            return Verification::failed('missing-input-response');
        }

        if (! $this->configured()) {
            return Verification::failed('not-configured');
        }

        try {
            $response = Http::asForm()
                ->timeout($this->timeout())
                ->connectTimeout($this->timeout())
                ->post($this->endpoint(), $this->payload($token, $ip));
        } catch (Throwable $e) {
            return Verification::failed('unreachable');
        }

        if (! $response->successful() || ! is_array($result = $response->json())) {
            return Verification::failed('unreachable');
        }

        return $this->result($result);
    }

    protected function payload(string $token, ?string $ip): array
    {
        return array_filter([
            'secret' => $this->secretKey(),
            'response' => $token,
            'remoteip' => $ip,
        ], fn ($value) => $value !== null);
    }

    protected function result(array $result): Verification
    {
        if (($result['success'] ?? false) !== true) {
            return Verification::failed(...$this->errorCodes($result));
        }

        return Verification::passed();
    }

    protected function errorCodes(array $result): array
    {
        $codes = $result['error-codes'] ?? [];

        return is_array($codes) && $codes ? array_values(array_filter($codes, 'is_string')) : ['invalid-input-response'];
    }

    protected function siteKey(): string
    {
        return (string) ($this->config['site_key'] ?? '');
    }

    protected function secretKey(): string
    {
        return (string) ($this->config['secret_key'] ?? '');
    }

    protected function timeout(): int|float
    {
        $timeout = $this->config['timeout'] ?? 5;

        return is_numeric($timeout) && $timeout > 0 ? $timeout + 0 : 5;
    }

    protected function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * The script that loads the provider and renders, resets and (for reCAPTCHA v3)
     * waits for each widget. It's safe to print more than once: it only runs once.
     */
    public static function loaderScript(): string
    {
        static $script;

        // Comments and indentation are left out of the page.
        return $script ??= trim(preg_replace(['#^\s*//.*$\n?#m', '#^\s+#m'], '', file_get_contents(__DIR__.'/../../resources/js/forms-captcha.js')));
    }

    public static function loader(): string
    {
        $nonce = Vite::cspNonce();

        return '<script'.($nonce ? ' nonce="'.e($nonce).'"' : '').'>'
            .static::loaderScript()
            .'</script>';
    }
}
