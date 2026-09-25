<?php

namespace SageCounseling\Helpers\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExceptionLoggerTest extends TestCase
{
    public function test_stub_implementation_receives_exception_with_optional_context(): void
    {
        $logger = new SpyExceptionLogger();
        $exception = new RuntimeException('boom');

        $logger->logException($exception, 'custom message', 'someFunction');

        $this->assertSame([
            ['exception' => $exception, 'customMessage' => 'custom message', 'function' => 'someFunction'],
        ], $logger->received);
    }

    public function test_stub_implementation_accepts_exception_only(): void
    {
        $logger = new SpyExceptionLogger();
        $exception = new RuntimeException('boom');

        $logger->logException($exception);

        $this->assertSame([
            ['exception' => $exception, 'customMessage' => null, 'function' => null],
        ], $logger->received);
    }
}
