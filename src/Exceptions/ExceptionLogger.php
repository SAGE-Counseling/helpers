<?php

namespace SageCounseling\Helpers\Exceptions;

use Throwable;

/**
 * Contract for logging an exception. Consuming apps bind their own concrete
 * implementation (persistence mechanism, connection, table shape are app-owned).
 */
interface ExceptionLogger
{
    /**
     * @param  Throwable  $exception  The exception being logged.
     * @param  string|null  $customMessage  An optional message to attach alongside the exception.
     * @param  string|null  $callingFunction  The name of the calling function/context, for reference.
     */
    public function logException(Throwable $exception, ?string $customMessage = null, ?string $callingFunction = null): void;
}
