<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use SageCounseling\Helpers\Notifications\AdminMessage;
use SageCounseling\Helpers\Notifications\ChannelSender;

final class SpyChannelSender implements ChannelSender
{
    /** @var list<AdminMessage> */
    public array $received = [];

    public function send(AdminMessage $message): void
    {
        $this->received[] = $message;
    }
}
