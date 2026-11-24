<?php

namespace NineteenNinetyFour\FormsCaptcha\Drivers;

use NineteenNinetyFour\FormsCaptcha\Verification;

/**
 * Google reCAPTCHA v3. There's nothing to tick: Google scores each visit
 * from 0 (a bot) to 1 (a person). Tokens made for another action are
 * refused, so a token minted for one purpose can't be replayed somewhere
 * else, and visits scoring under the "score" option are low scores.
 */
class RecaptchaV3 extends Recaptcha
{
    public function handle(): string
    {
        return 'recaptcha_v3';
    }

    protected function scriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js?render='.rawurlencode($this->siteKey());
    }

    protected function widgetAttributes(array $options): array
    {
        return ['data-action' => $this->action()];
    }

    /**
     * The script asks Google for a token as the form is submitted, puts it here, then submits again.
     */
    protected function widgetContents(): string
    {
        return '<input type="hidden" name="g-recaptcha-response" value="">';
    }

    public function action(): string
    {
        $action = $this->config['action'] ?? null;

        // Google allows letters, digits, slashes and underscores.
        return is_string($action) && preg_match('#^[A-Za-z0-9/_]+$#', $action) ? $action : 'submit';
    }

    public function score(): float
    {
        $score = $this->config['score'] ?? null;

        return is_numeric($score) ? (float) $score : 0.5;
    }

    protected function result(array $result): Verification
    {
        if (($result['success'] ?? false) !== true) {
            return Verification::failed(...$this->errorCodes($result));
        }

        if (($result['action'] ?? null) !== $this->action()) {
            return Verification::failed('action-mismatch');
        }

        if (! is_numeric($result['score'] ?? null)) {
            return Verification::failed('missing-score');
        }

        $score = (float) $result['score'];

        return $score >= $this->score() ? Verification::passed($score) : Verification::lowScore($score);
    }
}
