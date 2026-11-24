<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\Form;
use Statamic\Facades\Site;

class SubmitTest extends TestCase
{
    const ERROR = "We couldn't check you're not a robot. Please try again.";

    #[Test]
    public function a_valid_token_is_accepted_and_not_stored()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertProviderSent('the-token');

        $submission = $form->submissions()->first();
        $this->assertSame('ada@example.com', $submission->get('email'));
        $this->assertArrayNotHasKey('captcha', $submission->data()->all());
        $this->assertArrayNotHasKey('cf-turnstile-response', $submission->data()->all());
        $this->assertFalse($submission->isSpam());
    }

    #[Test]
    public function the_visitors_ip_is_sent_to_the_provider()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token']);

        Http::assertSent(fn ($request) => $request['remoteip'] === '203.0.113.9');
    }

    #[Test]
    public function a_token_posted_under_the_fields_handle_is_accepted_too()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'captcha' => 'the-token'])
            ->assertSessionHasNoErrors();

        $this->assertProviderSent('the-token');
        $this->assertArrayNotHasKey('captcha', $form->submissions()->first()->data()->all());
    }

    #[Test]
    public function a_missing_token_is_refused_without_asking_the_provider()
    {
        $form = $this->contactForm();
        Http::fake();
        Event::fake([FormSubmitted::class]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com'])
            ->assertSessionHasErrorsIn('form.contact', ['captcha' => self::ERROR]);

        Http::assertNothingSent();
        Event::assertNotDispatched(FormSubmitted::class);
        $this->assertCount(0, $this->kept($form));
    }

    #[Test]
    public function an_invalid_token_is_refused()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => false, 'error-codes' => ['invalid-input-response']]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'forged'])
            ->assertSessionHasErrorsIn('form.contact', ['captcha' => self::ERROR]);

        $this->assertCount(0, $this->kept($form));
    }

    #[Test]
    public function a_token_that_isnt_a_string_is_refused()
    {
        $form = $this->contactForm();
        Http::fake();

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => ['a', 'b']])
            ->assertSessionHasErrorsIn('form.contact', ['captcha']);

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unreachable_provider_refuses_the_submission()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, [], 503);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token'])
            ->assertSessionHasErrorsIn('form.contact', ['captcha']);
    }

    #[Test]
    public function the_other_fields_are_still_checked()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['cf-turnstile-response' => 'the-token'])
            ->assertSessionHasErrorsIn('form.contact', ['email']);
    }

    #[Test]
    public function the_honeypot_still_works()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token', 'winnie' => 'bot'])
            ->assertSessionHasNoErrors();

        $this->assertCount(0, $this->kept($form));
    }

    #[Test]
    public function an_ajax_submission_gets_the_error_as_json()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => false]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'used'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(400)
            ->assertJson(['error' => ['captcha' => self::ERROR], 'errors' => [self::ERROR]]);
    }

    #[Test]
    public function an_ajax_submission_that_passes_gets_success()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJson(['success' => true, 'submission_created' => true])
            ->assertJsonMissingPath('submission.captcha');
    }

    #[Test]
    public function precognition_doesnt_use_up_the_token()
    {
        $form = $this->contactForm();
        Http::fake();

        $this->post($form->actionUrl(), ['email' => 'ada@example.com'], ['Precognition' => 'true', 'Accept' => 'application/json'])
            ->assertNoContent()
            ->assertHeader('Precognition-Success', 'true');

        Http::assertNothingSent();
    }

    #[Test]
    public function the_provider_is_asked_once_per_request()
    {
        $form = $this->contactForm();
        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function the_error_is_translated_for_the_site()
    {
        app('translator')->addLines(['messages.failed' => 'Bitte bestätige, dass du kein Roboter bist.'], 'de', 'forms-captcha');
        Site::setSites([
            'default' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en_GB'],
            'german' => ['name' => 'Deutsch', 'url' => 'http://localhost/de/', 'locale' => 'de_DE', 'lang' => 'de'],
        ]);

        $form = $this->contactForm();
        Http::fake();

        $this->post($form->actionUrl(), ['email' => 'ada@example.com'], ['Referer' => 'http://localhost/de/kontakt'])
            ->assertSessionHasErrorsIn('form.contact', ['captcha' => 'Bitte bestätige, dass du kein Roboter bist.']);
    }

    #[Test]
    public function the_null_driver_lets_everything_through()
    {
        $this->captchaConfig(['driver' => 'null']);
        $form = $this->contactForm();
        Http::fake();

        $this->post($form->actionUrl(), ['email' => 'ada@example.com'])->assertSessionHasNoErrors();

        Http::assertNothingSent();
        $this->assertCount(1, $form->submissions());
    }

    #[Test]
    public function without_keys_every_submission_is_refused()
    {
        $this->captchaConfig(['site_key' => null]);
        $form = $this->contactForm();
        Http::fake();

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'cf-turnstile-response' => 'the-token'])
            ->assertSessionHasErrorsIn('form.contact', ['captcha']);

        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('providers')]
    public function each_provider_reads_its_own_input($driver, $endpoint, $input)
    {
        $this->captchaConfig(['driver' => $driver]);
        $form = $this->contactForm();
        $this->provider($endpoint, ['success' => true]);

        $this->post($form->actionUrl(), ['email' => 'ada@example.com', $input => 'the-token'])->assertSessionHasNoErrors();

        $this->assertProviderSent('the-token');
    }

    public static function providers(): array
    {
        return [
            'turnstile' => ['turnstile', self::TURNSTILE, 'cf-turnstile-response'],
            'hcaptcha' => ['hcaptcha', 'https://api.hcaptcha.com/siteverify', 'h-captcha-response'],
            'recaptcha' => ['recaptcha', self::RECAPTCHA, 'g-recaptcha-response'],
        ];
    }

    #[Test]
    public function on_a_multi_page_form_the_captcha_is_checked_on_its_own_page()
    {
        $form = tap(Form::make('enquiry')->title('Enquiry')->store(true)->formFields([
            'pages' => [
                ['id' => 'about_you', 'sections' => [['fields' => [
                    ['handle' => 'email', 'field' => ['type' => 'email', 'validate' => ['required']]],
                ]]]],
                ['id' => 'your_trip', 'sections' => [['fields' => [
                    ['handle' => 'destination', 'field' => ['type' => 'short_answer']],
                    ['handle' => 'captcha', 'field' => ['type' => 'captcha']],
                ]]]],
            ],
        ]))->save();

        $this->provider(self::TURNSTILE, ['success' => true]);

        $this->post($form->actionUrl(), ['_page' => 'about_you', 'email' => 'ada@example.com'])
            ->assertSessionHasNoErrors();
        Http::assertNothingSent();

        $this->post($form->actionUrl(), ['_page' => 'your_trip', 'destination' => 'Antigua'])
            ->assertSessionHasErrorsIn('form.enquiry', ['captcha']);
        Http::assertNothingSent();

        $this->post($form->actionUrl(), ['_page' => 'your_trip', 'destination' => 'Antigua', 'cf-turnstile-response' => 'the-token'])
            ->assertSessionHasNoErrors();

        $submission = $form->submissions()->first();
        $this->assertFalse($submission->isPartial());
        $this->assertSame('Antigua', $submission->get('destination'));
        $this->assertArrayNotHasKey('captcha', $submission->data()->all());
    }
}
