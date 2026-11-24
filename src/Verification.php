<?php

namespace NineteenNinetyFour\FormsCaptcha;

/**
 * What the provider said about a token.
 */
final class Verification
{
    /**
     * @param  list<string>  $errors  the provider's error codes, or our own ("missing-input-response", "unreachable")
     */
    public function __construct(
        public readonly bool $passed,
        public readonly ?float $score = null,
        public readonly bool $lowScore = false,
        public readonly array $errors = [],
    ) {
        //
    }

    public static function passed(?float $score = null): self
    {
        return new self(true, $score);
    }

    public static function failed(string ...$errors): self
    {
        return new self(false, errors: array_values($errors));
    }

    /**
     * A valid reCAPTCHA v3 token for the right action, scoring under the threshold.
     */
    public static function lowScore(float $score): self
    {
        return new self(false, $score, lowScore: true, errors: ['low-score']);
    }
}
