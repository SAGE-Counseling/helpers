<?php

namespace SageCounseling\Helpers\Tests\Notifications;

/**
 * Swapped in for the Log facade (Log::swap()) so tests can see what a failed
 * Delivery (or Debug) logs without a real Laravel log manager.
 */
final class SpyLogger
{
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $errors = [];

    /** @var list<string> */
    public array $debugs = [];

    public function error(string $message, array $context = []): void
    {
        $this->errors[] = ['message' => $message, 'context' => $context];
    }

    public function debug(string $message): void
    {
        $this->debugs[] = $message;
    }
}
