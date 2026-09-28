<?php

namespace SageCounseling\Helpers\Notifications;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One AdminMessage sent on one Channel — the step AdminAlert and AdminInfo
 * both hand off to. A failed Delivery is logged and never thrown back into
 * the code that raised the alert. See docs/adr/0003-queued-delivery-urgent-immediate.md.
 */
final class Delivery
{
    private static ?Dispatcher $dispatcher = null;

    private static ?string $connection = null;

    private static ?string $queue = null;

    /**
     * Queue Deliveries from now on instead of sending them inline. Called by
     * the service provider when sage-helpers.queue.enabled is on.
     */
    public static function queueOn(Dispatcher $dispatcher, ?string $connection = null, ?string $queue = null): void
    {
        self::$dispatcher = $dispatcher;
        self::$connection = $connection;
        self::$queue = $queue;
    }

    /**
     * Back to immediate Deliveries. Intended for tests.
     */
    public static function reset(): void
    {
        self::$dispatcher = null;
        self::$connection = null;
        self::$queue = null;
    }

    /**
     * Urgent Deliveries always go out inline: an urgent alert may be about the
     * queue itself (e.g. Horizon stopped while Redis is still up).
     */
    public static function send(Channel $channel, AdminMessage $message): void
    {
        $sender = ChannelRegistry::get($channel);

        // An unconfigured Channel (e.g. no Teams webhook) no-ops; don't put a
        // job on the queue that would do nothing.
        if ($sender instanceof NullChannelSender) {
            return;
        }

        try {
            if (self::$dispatcher && $message->severity !== Severity::Urgent) {
                $job = new QueuedDelivery($channel, $message);
                $job->connection = self::$connection;
                $job->queue = self::$queue;
                self::$dispatcher->dispatch($job);

                return;
            }

            $sender->send($message);
        } catch (Throwable $e) {
            self::logFailure($channel, $message, $e);
        }
    }

    /**
     * Logs a failed Delivery without the message body, which can carry client
     * identifiers.
     */
    public static function logFailure(Channel $channel, AdminMessage $message, Throwable $e): void
    {
        Log::error('Admin notification delivery failed', [
            'channel' => $channel->name,
            'severity' => $message->severity->name,
            'subject' => $message->subject,
            'exception' => $e,
        ]);
    }
}
