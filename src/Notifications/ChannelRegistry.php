<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Per-consuming-app registration point for concrete channel senders (e.g. a
 * Laravel Mail-backed sender, an HTTP-backed Teams webhook sender). A
 * consuming app registers these once, typically in a service provider's
 * boot() method.
 *
 * A Channel with nothing registered resolves to a NullChannelSender, so an
 * unconfigured channel (e.g. no Teams webhook set) no-ops rather than erroring.
 */
final class ChannelRegistry
{
    /** @var array<string, ChannelSender> */
    private static array $senders = [];

    public static function register(Channel $channel, ChannelSender $sender): void
    {
        self::$senders[$channel->name] = $sender;
    }

    public static function get(Channel $channel): ChannelSender
    {
        return self::$senders[$channel->name] ?? new NullChannelSender();
    }

    /**
     * Clears all registrations. Intended for tests.
     */
    public static function reset(): void
    {
        self::$senders = [];
    }
}
