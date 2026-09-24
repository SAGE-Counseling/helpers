<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelMap;
use SageCounseling\Helpers\Notifications\Severity;

class ChannelMapTest extends TestCase
{
    public function test_info_routes_to_mail_only(): void
    {
        $this->assertSame([Channel::Mail], ChannelMap::for(Severity::Info));
    }

    public function test_warning_routes_to_mail_and_teams(): void
    {
        $this->assertSame([Channel::Mail, Channel::Teams], ChannelMap::for(Severity::Warning));
    }

    public function test_urgent_routes_to_mail_and_teams(): void
    {
        $this->assertSame([Channel::Mail, Channel::Teams], ChannelMap::for(Severity::Urgent));
    }
}
