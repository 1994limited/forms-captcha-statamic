<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\SubmissionFinalized;

class SpamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->captchaConfig(['driver' => 'recaptcha_v3', 'score' => 0.5]);
    }

    private function score(float $score, string $action = 'submit'): void
    {
        $this->provider(self::RECAPTCHA, ['success' => true, 'score' => $score, 'action' => $action]);
    }

    private function submit($form, array $headers = [])
    {
        return $this->post($form->actionUrl(), ['email' => 'ada@example.com', 'g-recaptcha-response' => 'the-token'], $headers);
    }

    #[Test]
    public function a_good_score_is_accepted()
    {
        $form = $this->contactForm(['low_scores' => 'spam']);
        $this->score(0.9);

        $this->submit($form)->assertSessionHasNoErrors();

        $this->assertFalse($form->submissions()->first()->isSpam());
    }

    #[Test]
    public function a_low_score_shows_an_error_by_default()
    {
        $form = $this->contactForm();
        $this->score(0.1);

        $this->submit($form)->assertSessionHasErrorsIn('form.contact', ['captcha']);

        $this->assertCount(0, $this->kept($form));
    }

    #[Test]
    public function a_low_score_can_be_kept_as_spam()
    {
        Mail::fake();
        Event::fake([SubmissionFinalized::class]);

        $form = $this->contactForm(['low_scores' => 'spam']);
        $form->email([['to' => 'office@example.com', 'subject' => 'New enquiry']])->save();
        $this->score(0.1);

        // Like the honeypot, the visitor is told it went through.
        $this->submit($form)->assertSessionHasNoErrors()->assertRedirect();

        $submission = $this->kept($form)->first();
        $this->assertTrue($submission->isSpam());
        $this->assertSame('ada@example.com', $submission->get('email'));

        // No emails or connections: the submission is never finalised.
        Event::assertNotDispatched(SubmissionFinalized::class);
        Mail::assertNothingOutgoing();
    }

    #[Test]
    public function a_low_score_kept_as_spam_tells_ajax_nothing_was_created()
    {
        $form = $this->contactForm(['low_scores' => 'spam']);
        $this->score(0.1);

        $this->submit($form, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJson(['success' => true, 'submission_created' => false]);
    }

    #[Test]
    public function a_low_score_on_a_form_that_doesnt_store_submissions_is_dropped()
    {
        $form = $this->contactForm(['low_scores' => 'spam']);
        $form->store(false)->save();
        $this->score(0.1);

        $this->submit($form)->assertSessionHasNoErrors();

        $this->assertCount(0, $this->kept($form));
    }

    #[Test]
    public function the_config_sets_what_new_fields_do()
    {
        $this->captchaConfig(['low_scores' => 'spam']);
        $form = $this->contactForm();
        $this->score(0.1);

        $this->submit($form)->assertSessionHasNoErrors();

        $this->assertTrue($this->kept($form)->first()->isSpam());
    }

    #[Test]
    public function a_token_for_another_action_is_always_refused()
    {
        $form = $this->contactForm(['low_scores' => 'spam']);
        $this->score(0.9, 'login');

        $this->submit($form)->assertSessionHasErrorsIn('form.contact', ['captcha']);

        $this->assertCount(0, $this->kept($form));
    }

    #[Test]
    public function a_failed_check_is_refused_even_when_low_scores_are_kept()
    {
        $form = $this->contactForm(['low_scores' => 'spam']);
        $this->provider(self::RECAPTCHA, ['success' => false, 'error-codes' => ['timeout-or-duplicate']]);

        $this->submit($form)->assertSessionHasErrorsIn('form.contact', ['captcha']);
    }

    #[Test]
    public function a_low_score_doesnt_mark_the_next_submission()
    {
        $form = $this->contactForm(['low_scores' => 'spam']);
        Http::fake([self::RECAPTCHA => Http::sequence()
            ->push(['success' => true, 'score' => 0.1, 'action' => 'submit'])
            ->push(['success' => true, 'score' => 0.9, 'action' => 'submit'])]);

        $this->submit($form);
        $this->post($form->actionUrl(), ['email' => 'grace@example.com', 'g-recaptcha-response' => 'another-token']);

        $this->assertCount(2, $this->kept($form));
        $this->assertTrue($this->kept($form)->first(fn ($s) => $s->get('email') === 'ada@example.com')->isSpam());
        $this->assertFalse($this->kept($form)->first(fn ($s) => $s->get('email') === 'grace@example.com')->isSpam());
    }
}
