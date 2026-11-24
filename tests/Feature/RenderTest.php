<?php

namespace NineteenNinetyFour\FormsCaptcha\Tests\Feature;

use NineteenNinetyFour\FormsCaptcha\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RenderTest extends TestCase
{
    #[Test]
    public function the_fields_loop_renders_the_widget()
    {
        $this->contactForm();

        $html = $this->antlers('{{ form:contact }}{{ fields }}<div class="f">{{ field }}</div>{{ /fields }}{{ /form:contact }}');
        $this->assertStringContainsString('data-forms-captcha="turnstile"', $html);
    }
}
