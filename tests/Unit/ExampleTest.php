<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_limit_string_char_helper()
    {
        $this->assertEquals('Hello...', limitStringChar('Hello World', 5));
        $this->assertEquals('Hello World', limitStringChar('Hello World', 15));
        $this->assertEquals('', limitStringChar(null, 5));
    }
}
