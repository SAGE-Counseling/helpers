<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SageCounseling\Helpers\Notifications\AdminMessage;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\Delivery;
use SageCounseling\Helpers\Notifications\QueuedDelivery;
use SageCounseling\Helpers\Notifications\Severity;

class QueuedDeliveryTest extends TestCase
{
    protected function tearDown(): void
    {
        ChannelRegistry::reset();
        Delivery::reset();
        Facade::clearResolvedInstances();
    }

    public function test_handle_sends_through_the_registered_sender_even_while_queueing_is_on(): void
    {
        $teams = new SpyChannelSender();
        ChannelRegistry::register(Channel::Teams, $teams);
        $dispatcher = new SpyDispatcher();
        Delivery::queueOn($dispatcher);

        (new QueuedDelivery(Channel::Teams, new AdminMessage('slow import', Severity::Warning)))->handle();

        $this->assertCount(1, $teams->received);
        $this->assertSame('slow import', $teams->received[0]->message);
        $this->assertCount(0, $dispatcher->dispatched);
    }

    public function test_handle_lets_a_sender_failure_through_so_the_queue_retries(): void
    {
        ChannelRegistry::register(Channel::Teams, new ThrowingChannelSender());

        $this->expectException(RuntimeException::class);

        (new QueuedDelivery(Channel::Teams, new AdminMessage('slow import', Severity::Warning)))->handle();
    }

    public function test_job_is_encrypted_retried_and_never_held_until_commit(): void
    {
        $job = new QueuedDelivery(Channel::Mail, new AdminMessage('x', Severity::Info));

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $job);
        $this->assertFalse($job->afterCommit);
        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 120], $job->backoff);
        $this->assertSame(30, $job->timeout);
    }

    public function test_job_survives_serialization(): void
    {
        $job = new QueuedDelivery(Channel::Teams, new AdminMessage('slow import', Severity::Warning, 'Import'));
        $job->connection = 'redis';
        $job->queue = 'alerts';

        $copy = unserialize(serialize($job));

        $this->assertSame(Channel::Teams, $copy->channel);
        $this->assertSame('slow import', $copy->message->message);
        $this->assertSame(Severity::Warning, $copy->message->severity);
        $this->assertSame('Import', $copy->message->subject);
        $this->assertSame('redis', $copy->connection);
        $this->assertSame('alerts', $copy->queue);
    }

    public function test_final_failure_is_logged_without_the_body_and_raises_no_alert(): void
    {
        $log = new SpyLogger();
        Log::swap($log);
        $mail = new SpyChannelSender();
        ChannelRegistry::register(Channel::Mail, $mail);
        $dispatcher = new SpyDispatcher();
        Delivery::queueOn($dispatcher);

        $job = new QueuedDelivery(Channel::Teams, new AdminMessage('client 12345 import failed', Severity::Warning, 'Import'));
        $job->failed(new RuntimeException('webhook 500'));

        $this->assertCount(1, $log->errors);
        $context = $log->errors[0]['context'];
        $this->assertSame('Teams', $context['channel']);
        $this->assertSame('Warning', $context['severity']);
        $this->assertSame('Import', $context['subject']);
        $this->assertSame('webhook 500', $context['exception']->getMessage());
        $this->assertStringNotContainsString('12345', json_encode($log->errors[0]));
        $this->assertCount(0, $mail->received);
        $this->assertCount(0, $dispatcher->dispatched);
    }
}
