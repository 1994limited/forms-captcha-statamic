<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Illuminate\Support\Facades\Http;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SubmitTest extends TestCase
{
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
        $this->assertNotNull($submission);
        $this->assertSame('ada@example.com', $submission->get('email'));
        $this->assertNull($submission->get('captcha'));
        $this->assertNull($submission->get('cf-turnstile-response'));
        $this->assertArrayNotHasKey('captcha', $submission->data()->all());
    }

    #[Test]
    public function a_missing_token_is_refused_without_asking_the_provider()
    {
        $form = $this->contactForm();
        Http::fake();

        $this->post($form->actionUrl(), ['email' => 'ada@example.com'])
            ->assertSessionHasErrors(['captcha'], null, 'form.contact');

        Http::assertNothingSent();
        $this->assertCount(0, $form->submissions());
    }
}
