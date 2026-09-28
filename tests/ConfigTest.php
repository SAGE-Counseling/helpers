<?php

namespace SageCounseling\Helpers\Tests;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('SAGE_ADMIN_EMAIL');
        putenv('SAGE_ADMIN_NAME');
        putenv('SAGE_TEAMS_WEBHOOK_URL');
        putenv('SAGE_TEAMS_APP_LABEL');
        putenv('APP_NAME');
        putenv('SAGE_ALERTS_QUEUE');
        putenv('SAGE_ALERTS_QUEUE_CONNECTION');
        putenv('SAGE_ALERTS_QUEUE_NAME');

        parent::tearDown();
    }

    public function test_config_reads_the_canonical_env_vars(): void
    {
        putenv('SAGE_ADMIN_EMAIL=alerts@sagecounseling.example');
        putenv('SAGE_ADMIN_NAME=SAGE Admin Alerts');
        putenv('SAGE_TEAMS_WEBHOOK_URL=https://tenant.webhook.office.com/webhookb2/abc');
        putenv('SAGE_TEAMS_APP_LABEL=RPS (Testing Environment)');
        putenv('SAGE_ALERTS_QUEUE=true');
        putenv('SAGE_ALERTS_QUEUE_CONNECTION=redis');
        putenv('SAGE_ALERTS_QUEUE_NAME=alerts');

        $config = require __DIR__.'/../config/sage-helpers.php';

        $this->assertSame([
            'admin' => [
                'email' => 'alerts@sagecounseling.example',
                'name' => 'SAGE Admin Alerts',
            ],
            'teams' => [
                'webhook_url' => 'https://tenant.webhook.office.com/webhookb2/abc',
                'app_label' => 'RPS (Testing Environment)',
            ],
            'queue' => [
                'enabled' => true,
                'connection' => 'redis',
                'name' => 'alerts',
            ],
        ], $config);
    }

    public function test_config_values_are_null_when_env_vars_are_unset(): void
    {
        $config = require __DIR__.'/../config/sage-helpers.php';

        $this->assertNull($config['admin']['email']);
        $this->assertNull($config['admin']['name']);
        $this->assertNull($config['teams']['webhook_url']);
        $this->assertNull($config['teams']['app_label']);
    }

    public function test_teams_app_label_falls_back_to_app_name(): void
    {
        putenv('APP_NAME=RPS');

        $config = require __DIR__.'/../config/sage-helpers.php';

        $this->assertSame('RPS', $config['teams']['app_label']);
    }

    public function test_queueing_is_off_by_default_on_the_default_queue(): void
    {
        $config = require __DIR__.'/../config/sage-helpers.php';

        $this->assertFalse($config['queue']['enabled']);
        $this->assertNull($config['queue']['connection']);
        $this->assertSame('default', $config['queue']['name']);
    }
}
