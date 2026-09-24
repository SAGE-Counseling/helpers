<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\AdminInfo;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\Severity;

class AdminInfoTest extends TestCase
{
    protected function tearDown(): void
    {
        ChannelRegistry::reset();
    }

    public function test_send_reaches_only_the_named_channel(): void
    {
        $mail = new SpyChannelSender();
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, $mail);
        ChannelRegistry::register(Channel::Teams, $teams);

        AdminInfo::send(Channel::Teams, 'deploy finished');

        $this->assertCount(0, $mail->received);
        $this->assertCount(1, $teams->received);
        $this->assertSame('deploy finished', $teams->received[0]->message);
        $this->assertSame(Severity::Info, $teams->received[0]->severity);
    }
}
