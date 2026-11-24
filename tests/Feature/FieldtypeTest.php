<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Facades\Statamic\Fields\FieldtypeRepository;
use Facades\Statamic\Forms\Fields\FormFieldtypeRepository;
use NineteenNinetyFour\FormsCaptcha\FormFieldtypes\CaptchaFormFieldtype;
use NineteenNinetyFour\FormsCaptcha\Rules\PassesCaptcha;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blink;
use Statamic\Facades\User;
use Statamic\Forms\Fields\FormField;

class FieldtypeTest extends TestCase
{
    private function formFieldtype(array $config = []): CaptchaFormFieldtype
    {
        return (new FormField('captcha', ['type' => 'captcha', ...$config]))->fieldtype();
    }

    #[Test]
    public function it_is_a_form_fieldtype_for_the_builder()
    {
        $fieldtype = FormFieldtypeRepository::find('captcha');

        $this->assertInstanceOf(CaptchaFormFieldtype::class, $fieldtype);
        $this->assertTrue($fieldtype->isSelectable());
        $this->assertFalse($fieldtype->collectsValue());
        $this->assertSame('captcha', $fieldtype::fieldtype());

        $array = $fieldtype->toArray();
        $this->assertSame('captcha', $array['handle']);
        $this->assertSame('Captcha', $array['title']);
        $this->assertSame('Stops bots with Cloudflare Turnstile, checked when the form is submitted.', $array['description']);
        $this->assertFalse($array['collects_value']);
        $this->assertSame('security-lock', $array['icon']);
        $this->assertContains('turnstile', $array['keywords']);
    }

    #[Test]
    public function the_fieldtype_it_wraps_is_only_for_forms()
    {
        $fieldtype = FieldtypeRepository::find('captcha');

        $this->assertFalse($fieldtype->selectable());
        $this->assertFalse($fieldtype->selectableInForms());
    }

    #[Test]
    public function the_builder_lists_it()
    {
        $this->contactForm();
        $user = tap(User::make()->email('admin@example.com')->makeSuper())->save();

        $response = $this->actingAs($user)->get(cp_route('forms.builder.edit', 'contact'))->assertOk();

        $page = json_decode(html_entity_decode(str($response->getContent())->after('data-page="')->before('"')), true);
        $fieldtypes = collect($page['props']['fieldtypes'] ?? []);

        $this->assertContains('captcha', $fieldtypes->pluck('handle'));
    }

    #[Test]
    public function the_description_says_when_the_keys_are_missing()
    {
        $this->captchaConfig(['secret_key' => null]);

        $this->assertStringContainsString('Set FORMS_CAPTCHA_SITE_KEY and FORMS_CAPTCHA_SECRET_KEY', $this->formFieldtype()->description());
    }

    #[Test]
    public function it_has_a_theme_and_size_for_widgets_you_can_see()
    {
        foreach (['turnstile', 'hcaptcha', 'recaptcha'] as $driver) {
            $this->captchaConfig(['driver' => $driver]);
            Blink::flush();

            $fields = $this->formFieldtype()->configFields()->all();

            $this->assertSame(['display', 'instructions', 'theme', 'size'], $fields->keys()->all(), $driver);
            $this->assertSame('auto', $fields->get('theme')->defaultValue());
        }
    }

    #[Test]
    public function it_has_a_low_scores_setting_for_recaptcha_v3()
    {
        $this->captchaConfig(['driver' => 'recaptcha_v3', 'low_scores' => 'spam']);

        $fields = $this->formFieldtype()->configFields()->all();

        $this->assertSame(['display', 'instructions', 'low_scores'], $fields->keys()->all());
        $this->assertSame('spam', $fields->get('low_scores')->defaultValue());
    }

    #[Test]
    public function it_becomes_a_captcha_field_that_isnt_listed()
    {
        $array = $this->formFieldtype(['display' => 'Are you human?', 'theme' => 'dark'])->toFieldArray();

        $this->assertSame('captcha', $array['type']);
        $this->assertFalse($array['listable']);
        $this->assertSame('Are you human?', $array['display']);
        $this->assertSame('dark', $array['theme']);
    }

    #[Test]
    public function the_field_checks_the_captcha_as_a_validation_rule()
    {
        $field = $this->formFieldtype()->toField();

        $rules = $field->rules()['captcha'];

        $this->assertContains('nullable', $rules);
        $this->assertCount(1, collect($rules)->whereInstanceOf(PassesCaptcha::class));
        $this->assertTrue(collect($rules)->whereInstanceOf(PassesCaptcha::class)->first()->implicit);
    }

    #[Test]
    public function the_field_has_no_value()
    {
        $fieldtype = $this->formFieldtype()->toField()->fieldtype();

        $this->assertNull($fieldtype->process('the-token'));
        $this->assertNull($fieldtype->preProcess('the-token'));
        $this->assertNull($fieldtype->augment('the-token'));
    }

    #[Test]
    public function it_renders_with_its_own_view()
    {
        $this->assertSame('forms-captcha::field', $this->formFieldtype()->view());
    }

    #[Test]
    public function it_hands_the_widget_to_the_view()
    {
        $data = $this->formFieldtype(['theme' => 'light', 'size' => 'compact'])->prepareFrontEndRenderable(['is_informative' => false]);

        $this->assertTrue($data['is_informative']);
        $this->assertSame('turnstile', $data['captcha_driver']);
        $this->assertSame('cf-turnstile-response', $data['captcha_input']);
        $this->assertStringContainsString('data-theme="light" data-size="compact"', (string) $data['captcha']);
    }
}
