<?php

namespace SageCounseling\Helpers\Notifications;

interface ChannelSender
{
    public function send(AdminMessage $message): void;
}
