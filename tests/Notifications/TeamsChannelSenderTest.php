<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\AdminInfo;
use SageCounseling\Helpers\Notifications\AdminMessage;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\Severity;
use SageCounseling\Helpers\Notifications\TeamsChannelSender;

class TeamsChannelSenderTest extends TestCase
{
    private const WEBHOOK_URL = 'https://example.environment.api.powerplatform.com/powerautomate/automations/direct/workflows/abc';

    protected function tearDown(): void
    {
        ChannelRegistry::reset();

        parent::tearDown();
    }

    public function test_posts_an_adaptive_card_envelope_to_the_configured_webhook_url(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, self::WEBHOOK_URL, 'RPS (Testing Environment)');

        $sender->send(new AdminMessage('Test message', Severity::Urgent));

        $this->assertCount(1, $poster->calls);
        $this->assertSame(self::WEBHOOK_URL, $poster->calls[0]['url']);
        $this->assertSame([
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => [
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'version' => '1.5',
                        'body' => [
                            [
                                'type' => 'TextBlock',
                                'wrap' => true,
                                'style' => 'heading',
                                'text' => 'URGENT — RPS (Testing Environment)',
                                'weight' => 'bolder',
                                'size' => 'large',
                            ],
                            [
                                'type' => 'TextBlock',
                                'text' => 'Test message',
                                'color' => 'Attention',
                                'wrap' => true,
                            ],
                        ],
                        'actions' => [],
                    ],
                ],
            ],
        ], $poster->calls[0]['payload']);
    }

    /**
     * @return array<string, array{Severity, string, string}>
     */
    public static function severityStyles(): array
    {
        return [
            'info' => [Severity::Info, 'Info', 'Good'],
            'warning' => [Severity::Warning, 'Warning', 'Warning'],
            'urgent' => [Severity::Urgent, 'URGENT', 'Attention'],
        ];
    }

    #[DataProvider('severityStyles')]
    public function test_maps_severity_to_heading_prefix_and_message_color(Severity $severity, string $prefix, string $color): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, self::WEBHOOK_URL, 'RPS');

        $sender->send(new AdminMessage('heads up', $severity));

        $body = $this->cardBody($poster);
        $this->assertSame("{$prefix} — RPS", $body[0]['text']);
        $this->assertSame($color, $body[1]['color']);
    }

    public function test_admin_info_messages_use_info_styling(): void
    {
        $poster = new SpyHttpPoster();
        ChannelRegistry::register(Channel::Teams, new TeamsChannelSender($poster, self::WEBHOOK_URL, 'RPS'));

        AdminInfo::send(Channel::Teams, 'nightly import finished');

        $body = $this->cardBody($poster);
        $this->assertSame('Info — RPS', $body[0]['text']);
        $this->assertSame('nightly import finished', $body[1]['text']);
        $this->assertSame('Good', $body[1]['color']);
    }

    public function test_renders_the_subject_as_its_own_bold_text_block_between_heading_and_message(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, self::WEBHOOK_URL, 'RPS');

        $sender->send(new AdminMessage('database unreachable', Severity::Urgent, 'DB outage'));

        $body = $this->cardBody($poster);
        $this->assertCount(3, $body);
        $this->assertSame('URGENT — RPS', $body[0]['text']);
        $this->assertSame([
            'type' => 'TextBlock',
            'text' => 'DB outage',
            'weight' => 'bolder',
            'wrap' => true,
        ], $body[1]);
        $this->assertSame('database unreachable', $body[2]['text']);
        $this->assertSame('Attention', $body[2]['color']);
    }

    public function test_omits_the_subject_block_when_none_given(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, self::WEBHOOK_URL, 'RPS');

        $sender->send(new AdminMessage('heads up', Severity::Warning));

        $body = $this->cardBody($poster);
        $this->assertCount(2, $body);
        $this->assertSame('heads up', $body[1]['text']);
    }

    public function test_heading_is_just_the_prefix_when_no_app_label_is_configured(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, self::WEBHOOK_URL, null);

        $sender->send(new AdminMessage('heads up', Severity::Warning));

        $this->assertSame('Warning', $this->cardBody($poster)[0]['text']);
    }

    public function test_heading_is_just_the_prefix_when_the_app_label_is_empty(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, self::WEBHOOK_URL, '');

        $sender->send(new AdminMessage('heads up', Severity::Warning));

        $this->assertSame('Warning', $this->cardBody($poster)[0]['text']);
    }

    public function test_no_ops_without_throwing_when_the_webhook_url_is_unset(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, null, 'RPS');

        $sender->send(new AdminMessage('heads up', Severity::Info));

        $this->assertCount(0, $poster->calls);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cardBody(SpyHttpPoster $poster): array
    {
        $this->assertCount(1, $poster->calls);

        return $poster->calls[0]['payload']['attachments'][0]['content']['body'];
    }
}
