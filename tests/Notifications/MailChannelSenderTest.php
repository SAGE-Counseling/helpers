<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\AdminMessage;
use SageCounseling\Helpers\Notifications\MailChannelSender;
use SageCounseling\Helpers\Notifications\Severity;

class MailChannelSenderTest extends TestCase
{
    public function test_sends_raw_mail_addressed_to_the_configured_admin(): void
    {
        $mailer = new FakeMailer();
        $sender = new MailChannelSender($mailer, 'admin@example.com', 'SAGE Admin');

        $sender->send(new AdminMessage('disk almost full', Severity::Warning, 'Disk space'));

        $this->assertCount(1, $mailer->rawCalls);
        $this->assertSame('disk almost full', $mailer->rawCalls[0]['text']);
        $this->assertSame(['admin@example.com' => 'SAGE Admin'], $mailer->rawCalls[0]['to']);
        $this->assertSame('Disk space', $mailer->rawCalls[0]['subject']);
    }

    public function test_addresses_by_email_alone_when_no_name_is_configured(): void
    {
        $mailer = new FakeMailer();
        $sender = new MailChannelSender($mailer, 'admin@example.com');

        $sender->send(new AdminMessage('heads up', Severity::Info));

        $this->assertSame('admin@example.com', $mailer->rawCalls[0]['to']);
    }

    public function test_falls_back_to_a_severity_based_subject_when_none_given(): void
    {
        $mailer = new FakeMailer();
        $sender = new MailChannelSender($mailer, 'admin@example.com');

        $sender->send(new AdminMessage('heads up', Severity::Urgent));

        $this->assertStringContainsString('Urgent', $mailer->rawCalls[0]['subject']);
    }
}
