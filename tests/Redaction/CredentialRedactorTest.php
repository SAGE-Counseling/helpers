<?php

namespace SageCounseling\Helpers\Tests\Redaction;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Redaction\CredentialRedactor;

class CredentialRedactorTest extends TestCase
{
    public function test_last_keeps_only_the_trailing_characters_behind_an_ellipsis(): void
    {
        $this->assertSame('...KLMNOPQRST', CredentialRedactor::last('ABCDEFGHIJKLMNOPQRST'));
        $this->assertSame('...RST', CredentialRedactor::last('ABCDEFGHIJKLMNOPQRST', 3));
    }
}
