<?php

namespace SageCounseling\Helpers\Tests\Exceptions;

use SageCounseling\Helpers\Exceptions\ExceptionLogger;
use Throwable;

final class SpyExceptionLogger implements ExceptionLogger
{
    /** @var list<array{exception: Throwable, customMessage: ?string, callingFunction: ?string}> */
    public array $received = [];

    public function logException(Throwable $exception, ?string $customMessage = null, ?string $callingFunction = null): void
    {
        $this->received[] = [
            'exception' => $exception,
            'customMessage' => $customMessage,
            'callingFunction' => $callingFunction,
        ];
    }
}
