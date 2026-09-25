<?php

namespace SageCounseling\Helpers\Tests\Laravel;

use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Laravel\SageHelpersServiceProvider;

class SageHelpersServiceProviderTest extends TestCase
{
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
}
