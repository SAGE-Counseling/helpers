<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use RuntimeException;
use SageCounseling\Helpers\Notifications\AdminMessage;
use SageCounseling\Helpers\Notifications\ChannelSender;

final class ThrowingChannelSender implements ChannelSender
{
    public function send(AdminMessage $message): void
    {
        throw new RuntimeException('transport down');
    }
}
