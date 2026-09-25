<?php

namespace SageCounseling\Helpers\Exceptions;

use Throwable;

/**
 * Contract for logging an exception. Consuming apps bind their own concrete
 * implementation (persistence mechanism, connection, table shape are app-owned).
 */
interface ExceptionLogger
{
    public function logException(Throwable $exception, ?string $customMessage = null, ?string $function = null): void;
}
