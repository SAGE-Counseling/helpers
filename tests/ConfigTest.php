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

        parent::tearDown();
    }

    public function test_config_reads_the_three_canonical_env_vars(): void
    {
        putenv('SAGE_ADMIN_EMAIL=alerts@sagecounseling.example');
        putenv('SAGE_ADMIN_NAME=SAGE Admin Alerts');
        putenv('SAGE_TEAMS_WEBHOOK_URL=https://tenant.webhook.office.com/webhookb2/abc');

        $config = require __DIR__.'/../config/sage-helpers.php';

        $this->assertSame([
            'admin' => [
                'email' => 'alerts@sagecounseling.example',
                'name' => 'SAGE Admin Alerts',
            ],
            'teams' => [
                'webhook_url' => 'https://tenant.webhook.office.com/webhookb2/abc',
            ],
        ], $config);
    }

    public function test_config_values_are_null_when_env_vars_are_unset(): void
    {
        $config = require __DIR__.'/../config/sage-helpers.php';

        $this->assertNull($config['admin']['email']);
        $this->assertNull($config['admin']['name']);
        $this->assertNull($config['teams']['webhook_url']);
    }
}
