<?php

namespace SageCounseling\Helpers\Tests\Laravel;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Laravel\SageHelpersServiceProvider;
use SageCounseling\Helpers\Notifications\AdminAlert;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\HttpPoster;
use SageCounseling\Helpers\Notifications\Severity;
use SageCounseling\Helpers\Tests\Notifications\FakeMailer;
use SageCounseling\Helpers\Tests\Notifications\SpyHttpPoster;

class SageHelpersServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        ChannelRegistry::reset();
    }

    public function test_register_merges_the_package_config(): void
    {
        $app = new FakeApplication();
        $provider = new SageHelpersServiceProvider($app);

        $provider->register();

        $config = $app->config->get('sage-helpers');

        $this->assertSame(env('SAGE_ADMIN_EMAIL'), $config['admin']['email']);
        $this->assertArrayHasKey('name', $config['admin']);
        $this->assertArrayHasKey('webhook_url', $config['teams']);
    }

    public function test_boot_publishes_the_config_file_to_the_app_config_path(): void
    {
        $app = new FakeApplication();
        $provider = new SageHelpersServiceProvider($app);

        $provider->boot();

        $published = ServiceProvider::pathsToPublish(SageHelpersServiceProvider::class);

        $this->assertCount(1, $published);
        $this->assertSame($app->configPath('sage-helpers.php'), array_values($published)[0]);
        $this->assertSame(
            realpath(__DIR__.'/../../config/sage-helpers.php'),
            realpath(array_key_first($published))
        );
    }

    public function test_boot_registers_the_config_publish_group(): void
    {
        $app = new FakeApplication();
        $provider = new SageHelpersServiceProvider($app);

        $provider->boot();

        $this->assertContains('sage-helpers-config', ServiceProvider::publishableGroups());
    }

    public function test_boot_registers_a_mail_channel_sender_when_admin_email_is_configured(): void
    {
        $app = new FakeApplication();
        $mailer = new FakeMailer();
        $app->bind(Mailer::class, $mailer);

        $provider = new SageHelpersServiceProvider($app);
        $provider->register();
        $app->config->set('sage-helpers', [
            'admin' => ['email' => 'admin@example.com', 'name' => 'SAGE Admin'],
            'teams' => ['webhook_url' => null],
        ]);

        $provider->boot();
        AdminAlert::send('database unreachable', Severity::Urgent);

        $this->assertCount(1, $mailer->rawCalls);
        $this->assertSame('database unreachable', $mailer->rawCalls[0]['text']);
        $this->assertSame(['admin@example.com' => 'SAGE Admin'], $mailer->rawCalls[0]['to']);
    }

    public function test_boot_registers_a_teams_channel_sender_when_webhook_url_is_configured(): void
    {
        $app = new FakeApplication();
        $poster = new SpyHttpPoster();

        $provider = new SageHelpersServiceProvider($app);
        $provider->register();
        $app->bind(HttpPoster::class, $poster);
        $app->config->set('sage-helpers', [
            'admin' => ['email' => null, 'name' => null],
            'teams' => ['webhook_url' => 'https://example.webhook.office.com/webhookb2/abc'],
        ]);

        $provider->boot();
        AdminAlert::send('database unreachable', Severity::Urgent, 'DB outage');

        $this->assertCount(1, $poster->calls);
        $this->assertSame('https://example.webhook.office.com/webhookb2/abc', $poster->calls[0]['url']);
        $this->assertSame('DB outage: database unreachable', $poster->calls[0]['payload']['text']);
    }

    public function test_boot_registers_both_senders_when_both_are_configured(): void
    {
        $app = new FakeApplication();
        $mailer = new FakeMailer();
        $poster = new SpyHttpPoster();

        $provider = new SageHelpersServiceProvider($app);
        $provider->register();
        $app->bind(Mailer::class, $mailer);
        $app->bind(HttpPoster::class, $poster);
        $app->config->set('sage-helpers', [
            'admin' => ['email' => 'admin@example.com', 'name' => 'SAGE Admin'],
            'teams' => ['webhook_url' => 'https://example.webhook.office.com/webhookb2/abc'],
        ]);

        $provider->boot();
        AdminAlert::send('database unreachable', Severity::Urgent, 'DB outage');

        $this->assertCount(1, $mailer->rawCalls);
        $this->assertCount(1, $poster->calls);
    }

    public function test_boot_does_not_register_any_sender_when_nothing_is_configured(): void
    {
        $app = new FakeApplication();
        $provider = new SageHelpersServiceProvider($app);

        $provider->register();
        $provider->boot();
        AdminAlert::send('heads up', Severity::Info);

        $this->assertTrue(true, 'AdminAlert::send() must no-op instead of throwing with no sender registered.');
    }
}
