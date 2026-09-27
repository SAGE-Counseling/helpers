<?php

namespace SageCounseling\Helpers\Notifications;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

/**
 * A Delivery run on a queue worker instead of inline. Encrypted because alert
 * text can carry client identifiers. Sends through ChannelRegistry, which
 * only ever holds real senders, so the job can't re-queue itself.
 */
final class QueuedDelivery implements ShouldQueue, ShouldBeEncrypted
{
    public ?string $connection = null;

    public ?string $queue = null;

    /** Dispatch right away, even inside a DB transaction that later rolls back. */
    public bool $afterCommit = false;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public int $timeout = 30;

    public function __construct(
        public readonly Channel $channel,
        public readonly AdminMessage $message,
    ) {
    }

    public function handle(): void
    {
        ChannelRegistry::get($this->channel)->send($this->message);
    }

    /**
     * Called by Laravel once every retry is used up. Logs only — never raises
     * another alert, which could fail the same way.
     */
    public function failed(Throwable $e): void
    {
        Delivery::logFailure($this->channel, $this->message, $e);
    }
}
