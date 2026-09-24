<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\AdminAlert;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\Severity;

class AdminAlertTest extends TestCase
{
    protected function tearDown(): void
    {
        ChannelRegistry::reset();
    }

    public function test_info_alert_only_reaches_mail(): void
    {
        $mail = new SpyChannelSender();
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, $mail);
        ChannelRegistry::register(Channel::Teams, $teams);

        AdminAlert::send('disk almost full', Severity::Info);

        $this->assertCount(1, $mail->received);
        $this->assertSame('disk almost full', $mail->received[0]->message);
        $this->assertSame(Severity::Info, $mail->received[0]->severity);
        $this->assertCount(0, $teams->received);
    }

    public function test_urgent_alert_reaches_mail_and_teams(): void
    {
        $mail = new SpyChannelSender();
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, $mail);
        ChannelRegistry::register(Channel::Teams, $teams);

        AdminAlert::send('database unreachable', Severity::Urgent, 'DB outage');

        $this->assertCount(1, $mail->received);
        $this->assertCount(1, $teams->received);
        $this->assertSame('DB outage', $teams->received[0]->subject);
        $this->assertSame(Severity::Urgent, $teams->received[0]->severity);
    }

    public function test_unregistered_channel_no_ops_instead_of_throwing(): void
    {
        // No senders registered at all — Teams has no webhook configured, say.
        $this->expectNotToPerformAssertions();

        AdminAlert::send('heads up', Severity::Warning);
    }
}
