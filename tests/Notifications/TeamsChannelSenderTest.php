<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\AdminMessage;
use SageCounseling\Helpers\Notifications\Severity;
use SageCounseling\Helpers\Notifications\TeamsChannelSender;

class TeamsChannelSenderTest extends TestCase
{
    public function test_posts_to_the_configured_webhook_url(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, 'https://example.webhook.office.com/webhookb2/abc');

        $sender->send(new AdminMessage('database unreachable', Severity::Urgent, 'DB outage'));

        $this->assertCount(1, $poster->calls);
        $this->assertSame('https://example.webhook.office.com/webhookb2/abc', $poster->calls[0]['url']);
        $this->assertSame('DB outage: database unreachable', $poster->calls[0]['payload']['text']);
    }

    public function test_omits_the_subject_prefix_when_none_given(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, 'https://example.webhook.office.com/webhookb2/abc');

        $sender->send(new AdminMessage('heads up', Severity::Warning));

        $this->assertSame('heads up', $poster->calls[0]['payload']['text']);
    }

    public function test_no_ops_without_throwing_when_the_webhook_url_is_unset(): void
    {
        $poster = new SpyHttpPoster();
        $sender = new TeamsChannelSender($poster, null);

        $sender->send(new AdminMessage('heads up', Severity::Info));

        $this->assertCount(0, $poster->calls);
    }
}
