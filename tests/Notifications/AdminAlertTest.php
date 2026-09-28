<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\AdminAlert;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\Delivery;
use SageCounseling\Helpers\Notifications\QueuedDelivery;
use SageCounseling\Helpers\Notifications\Severity;

class AdminAlertTest extends TestCase
{
    protected function tearDown(): void
    {
        ChannelRegistry::reset();
        Delivery::reset();
        Facade::clearResolvedInstances();
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

    public function test_a_failing_channel_is_logged_and_the_next_channel_still_gets_its_delivery(): void
    {
        $log = new SpyLogger();
        Log::swap($log);
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, new ThrowingChannelSender());
        ChannelRegistry::register(Channel::Teams, $teams);

        AdminAlert::send('client 12345 import failed', Severity::Urgent, 'Import error');

        $this->assertCount(1, $teams->received);
        $this->assertCount(1, $log->errors);
        $context = $log->errors[0]['context'];
        $this->assertSame('Mail', $context['channel']);
        $this->assertSame('Urgent', $context['severity']);
        $this->assertSame('Import error', $context['subject']);
        $this->assertSame('transport down', $context['exception']->getMessage());
        $this->assertStringNotContainsString('12345', json_encode($log->errors[0]['message']));
        $this->assertArrayNotHasKey('message', $context);
    }

    public function test_queued_warning_dispatches_one_job_per_channel_instead_of_sending(): void
    {
        $mail = new SpyChannelSender();
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, $mail);
        ChannelRegistry::register(Channel::Teams, $teams);
        $dispatcher = new SpyDispatcher();
        Delivery::queueOn($dispatcher, 'redis', 'alerts');

        AdminAlert::send('slow import', Severity::Warning);

        $this->assertCount(0, $mail->received);
        $this->assertCount(0, $teams->received);
        $this->assertCount(2, $dispatcher->dispatched);
        [$first, $second] = $dispatcher->dispatched;
        $this->assertInstanceOf(QueuedDelivery::class, $first);
        $this->assertSame(Channel::Mail, $first->channel);
        $this->assertSame(Channel::Teams, $second->channel);
        $this->assertSame('slow import', $second->message->message);
        $this->assertSame('redis', $first->connection);
        $this->assertSame('alerts', $first->queue);
    }

    public function test_queueing_skips_channels_that_are_not_configured(): void
    {
        ChannelRegistry::register(Channel::Mail, new SpyChannelSender());
        $dispatcher = new SpyDispatcher();
        Delivery::queueOn($dispatcher);

        AdminAlert::send('slow import', Severity::Warning);

        $this->assertCount(1, $dispatcher->dispatched);
        $this->assertSame(Channel::Mail, $dispatcher->dispatched[0]->channel);
    }

    public function test_urgent_is_sent_immediately_even_when_queueing_is_on(): void
    {
        $mail = new SpyChannelSender();
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, $mail);
        ChannelRegistry::register(Channel::Teams, $teams);
        $dispatcher = new SpyDispatcher();
        Delivery::queueOn($dispatcher, 'redis', 'alerts');

        AdminAlert::send('horizon is down', Severity::Urgent);

        $this->assertCount(0, $dispatcher->dispatched);
        $this->assertCount(1, $mail->received);
        $this->assertCount(1, $teams->received);
    }
}
