<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RenderTest extends TestCase
{
    #[Test]
    public function the_fields_loop_renders_the_widget_without_a_label()
    {
        $this->contactForm();

        $html = $this->antlers(<<<'ANTLERS'
            {{ form:contact }}
                {{ fields }}
                    {{ if is_informative }}{{ field }}{{ else }}<label for="{{ id }}">{{ display }}</label>{{ field }}{{ /if }}
                {{ /fields }}
            {{ /form:contact }}
            ANTLERS);

        $this->assertStringContainsString('<div class="forms-captcha" id="contact-form-captcha-field"><div data-forms-captcha="turnstile" data-sitekey="site-key"', $html);
        $this->assertStringContainsString('<label for="contact-form-email-field">Email</label>', $html);
        $this->assertStringNotContainsString('<label for="contact-form-captcha-field">', $html);
        $this->assertSame(1, substr_count($html, 'if (window.FormsCaptcha) {'));
    }

    #[Test]
    public function the_widget_uses_the_fields_theme_and_size()
    {
        $this->contactForm(['theme' => 'dark', 'size' => 'compact']);

        $html = $this->antlers('{{ form:contact }}{{ fields }}{{ field }}{{ /fields }}{{ /form:contact }}');

        $this->assertStringContainsString('data-theme="dark" data-size="compact"', $html);
    }

    #[Test]
    public function the_error_shows_under_the_widget()
    {
        $this->contactForm();
        session()->put('errors', (new ViewErrorBag)->put('form.contact', new MessageBag(['captcha' => ["We couldn't check you're not a robot."]])));

        $html = $this->antlers('{{ form:contact }}{{ fields }}{{ field }}{{ /fields }}{{ /form:contact }}');

        $this->assertStringContainsString('<div class="forms-captcha" id="contact-form-captcha-field" role="group" aria-describedby="contact-form-captcha-field-error">', $html);
        $this->assertStringContainsString('<p class="forms-captcha-error" id="contact-form-captcha-field-error">We couldn&#039;t check you&#039;re not a robot.</p>', $html);
    }

    #[Test]
    public function a_custom_template_places_it_with_the_tag()
    {
        $this->contactForm();
        session()->put('errors', (new ViewErrorBag)->put('form.contact', new MessageBag(['captcha' => ['Try again.']])));

        $html = $this->antlers(<<<'ANTLERS'
            {{ form:contact }}
                <input type="email" name="email">
                {{ forms_captcha }}
                {{ if errors }}<ul>{{ errors }}<li>{{ value }}</li>{{ /errors }}</ul>{{ /if }}
                <button>Send</button>
            {{ /form:contact }}
            ANTLERS);

        $this->assertStringContainsString('<input type="email" name="email">', $html);
        $this->assertMatchesRegularExpression('#<div class="forms-captcha"[^>]*><div data-forms-captcha="turnstile"#', $html);
        $this->assertStringContainsString('<li>Try again.</li>', $html);
        $this->assertStringContainsString('<button>Send</button>', $html);
    }

    #[Test]
    public function the_tag_picks_a_field_by_handle()
    {
        $this->contactForm();

        $this->assertStringContainsString('data-forms-captcha', $this->antlers('{{ form:contact }}{{ forms_captcha handle="captcha" }}{{ /form:contact }}'));
        $this->assertStringNotContainsString('data-forms-captcha', $this->antlers('{{ form:contact }}{{ forms_captcha handle="email" }}{{ /form:contact }}'));
        $this->assertSame('', $this->antlers('{{ forms_captcha }}'));
    }

    #[Test]
    public function blade_renders_it_in_the_fields_loop()
    {
        $this->contactForm();

        $html = view('blade-loop')->render();

        $this->assertStringContainsString('<div class="field"><div class="forms-captcha" id="contact-form-captcha-field"><div data-forms-captcha="turnstile"', $html);
    }

    #[Test]
    public function blade_places_it_with_the_tag()
    {
        $this->contactForm();

        $html = view('blade-tag')->render();

        $this->assertStringContainsString('data-forms-captcha="turnstile"', $html);
        $this->assertStringContainsString('<button>Send</button>', $html);
    }

    #[Test]
    public function it_works_with_the_alpine_js_driver()
    {
        $this->contactForm();

        $html = $this->antlers('{{ form:contact js="alpine" }}{{ fields }}{{ field }}{{ /fields }}{{ /form:contact }}');

        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('data-forms-captcha="turnstile"', $html);
    }

    #[Test]
    public function without_keys_it_leaves_a_note_in_the_source()
    {
        $this->captchaConfig(['secret_key' => '']);
        $this->contactForm();

        $html = $this->antlers('{{ form:contact }}{{ fields }}{{ field }}{{ /fields }}{{ /form:contact }}');

        $this->assertStringContainsString('<!-- Forms Captcha: set FORMS_CAPTCHA_SITE_KEY and FORMS_CAPTCHA_SECRET_KEY to show the captcha. -->', $html);
        $this->assertStringNotContainsString('data-forms-captcha', $html);
    }

    #[Test]
    public function the_null_driver_renders_nothing()
    {
        $this->captchaConfig(['driver' => 'null']);
        $this->contactForm();

        $html = $this->antlers('{{ form:contact }}{{ fields }}{{ field }}{{ /fields }}{{ /form:contact }}');

        $this->assertStringContainsString('<div class="forms-captcha" id="contact-form-captcha-field"></div>', $html);
    }

    #[Test]
    public function recaptcha_v3_renders_the_hidden_input()
    {
        $this->captchaConfig(['driver' => 'recaptcha_v3', 'action' => 'contact']);
        $this->contactForm();

        $html = $this->antlers('{{ form:contact }}{{ fields }}{{ field }}{{ /fields }}{{ /form:contact }}');

        $this->assertStringContainsString('data-action="contact"><input type="hidden" name="g-recaptcha-response" value=""></div>', $html);
    }
}
