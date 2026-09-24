<?php

namespace SageCounseling\Helpers\Tests;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Str;

class StrTest extends TestCase
{
    public function test_slug_converts_to_lowercase_with_separator(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World'));
    }
}
