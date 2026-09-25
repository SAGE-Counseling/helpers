<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use Illuminate\Contracts\Mail\Mailer;

/**
 * Minimal stand-in for Illuminate\Contracts\Mail\Mailer, covering only what
 * MailChannelSender actually calls. Records each raw() send instead of
 * dispatching real mail.
 */
final class FakeMailer implements Mailer
{
    /** @var list<array{text: string, to: mixed, subject: ?string}> */
    public array $rawCalls = [];

    public function to($users)
    {
        throw new \LogicException('FakeMailer::to() is not used by MailChannelSender.');
    }

    public function bcc($users)
    {
        throw new \LogicException('FakeMailer::bcc() is not used by MailChannelSender.');
    }

    public function raw($text, $callback)
    {
        $message = new FakeMailMessage();
        $callback($message);

        $this->rawCalls[] = [
            'text' => $text,
            'to' => $message->to,
            'subject' => $message->subject,
        ];

        return null;
    }

    public function send($view, array $data = [], $callback = null)
    {
        throw new \LogicException('FakeMailer::send() is not used by MailChannelSender.');
    }
}
