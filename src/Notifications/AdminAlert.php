<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Single entry point for "something needs Admin's attention" — replaces the
 * per-site adminMessage()/adminErrorMessage()/warningEmail()/teamsInfo()/etc.
 * zoo. Severity picks the channels via the fixed ChannelMap; call sites never
 * pick channels directly. See docs/admin-notifications-contract.md and
 * docs/adr/0001-fixed-severity-channel-map.md.
 */
final class AdminAlert
{
    public static function send(string $message, Severity $severity = Severity::Info, ?string $subject = null): void
    {
        $adminMessage = new AdminMessage($message, $severity, $subject);

        foreach (ChannelMap::for($severity) as $channel) {
            ChannelRegistry::get($channel)->send($adminMessage);
        }
    }
}
