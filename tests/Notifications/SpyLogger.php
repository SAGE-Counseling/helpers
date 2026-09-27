<?php

namespace SageCounseling\Helpers\Tests\Notifications;

/**
 * Swapped in for the Log facade (Log::swap()) so tests can see what a failed
 * Delivery logs without a real Laravel log manager.
 */
final class SpyLogger
{
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $errors = [];

    public function error(string $message, array $context = []): void
    {
        $this->errors[] = ['message' => $message, 'context' => $context];
    }
}
