<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * A routine status message directed at exactly one explicitly-named Channel —
 * distinct from AdminAlert. Always informational; never touches the
 * ChannelMap. See docs/adr/0002-admininfo-separate-entry-point.md.
 */
final class AdminInfo
{
    public static function send(Channel $channel, string $message): void
    {
        ChannelRegistry::get($channel)->send(new AdminMessage($message, Severity::Info));
    }
}
