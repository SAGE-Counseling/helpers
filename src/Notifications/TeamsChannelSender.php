<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Delivers an AdminMessage to a Microsoft Teams incoming webhook. No-ops
 * when no webhook URL is configured, matching the "unconfigured channel
 * doesn't error" rule in docs/admin-notifications-contract.md.
 */
final class TeamsChannelSender implements ChannelSender
{
    public function __construct(
        private readonly HttpPoster $poster,
        private readonly ?string $webhookUrl,
    ) {
    }

    public function send(AdminMessage $message): void
    {
        if (! $this->webhookUrl) {
            return;
        }

        $this->poster->postJson($this->webhookUrl, ['text' => $this->format($message)]);
    }

    private function format(AdminMessage $message): string
    {
        return $message->subject ? "{$message->subject}: {$message->message}" : $message->message;
    }
}
