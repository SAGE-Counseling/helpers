<?php

namespace SageCounseling\Helpers\Tests\Notifications;

/**
 * Minimal stand-in for Illuminate\Mail\Message, covering only the fluent
 * calls MailChannelSender's raw() callback makes.
 */
final class FakeMailMessage
{
    public mixed $to = null;

    public ?string $subject = null;

    public function to(mixed $address): self
    {
        $this->to = $address;

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
