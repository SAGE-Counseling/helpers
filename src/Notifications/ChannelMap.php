<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Fixed, package-wide Severity -> Channel routing for AdminAlert. Not
 * configurable per site or per call site — see docs/adr/0001-fixed-severity-channel-map.md.
 *
 * Sms is not yet a Channel case; once implemented it joins the Urgent row.
 */
final class ChannelMap
{
    /**
     * @return list<Channel>
     */
    public static function for(Severity $severity): array
    {
        return match ($severity) {
            Severity::Info => [Channel::Mail],
            Severity::Warning => [Channel::Mail, Channel::Teams],
            Severity::Urgent => [Channel::Mail, Channel::Teams],
        };
    }
}
