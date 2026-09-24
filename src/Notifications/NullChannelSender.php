<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Used for a Channel with no sender registered (e.g. a site with no Teams
 * webhook configured). No-ops rather than erroring, per the admin-notifications
 * contract.
 */
final class NullChannelSender implements ChannelSender
{
    public function send(AdminMessage $message): void
    {
    }
}
