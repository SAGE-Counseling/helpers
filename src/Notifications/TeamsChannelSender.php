<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Delivers an AdminMessage to a Microsoft Teams Power Automate Workflows
 * webhook as an Adaptive Card. No-ops when no webhook URL is configured,
 * matching the "unconfigured channel doesn't error" rule in
 * docs/admin-notifications-contract.md.
 *
 * Workflows webhooks accept any POST with 202 and fail inside the flow if the
 * payload isn't an Adaptive Card envelope, so a legacy connector-style
 * {"text": ...} payload is silently dropped rather than rejected.
 */
final class TeamsChannelSender implements ChannelSender
{
    public function __construct(
        private readonly HttpPoster $poster,
        private readonly ?string $webhookUrl,
        private readonly ?string $appLabel = null,
    ) {
    }

    public function send(AdminMessage $message): void
    {
        if (! $this->webhookUrl) {
            return;
        }

        $this->poster->postJson($this->webhookUrl, [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => [
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'version' => '1.5',
                        'body' => $this->body($message),
                        'actions' => [],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function body(AdminMessage $message): array
    {
        [$prefix, $color] = match ($message->severity) {
            Severity::Info => ['Info', 'Good'],
            Severity::Warning => ['Warning', 'Warning'],
            Severity::Urgent => ['URGENT', 'Attention'],
        };

        $body = [[
            'type' => 'TextBlock',
            'wrap' => true,
            'style' => 'heading',
            'text' => $this->appLabel ? "{$prefix} — {$this->appLabel}" : $prefix,
            'weight' => 'bolder',
            'size' => 'large',
        ]];

        if ($message->subject) {
            $body[] = [
                'type' => 'TextBlock',
                'text' => $message->subject,
                'weight' => 'bolder',
                'wrap' => true,
            ];
        }

        $body[] = [
            'type' => 'TextBlock',
            'text' => $message->message,
            'color' => $color,
            'wrap' => true,
        ];

        return $body;
    }
}
