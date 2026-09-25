<?php

namespace SageCounseling\Helpers\Laravel;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\ServiceProvider;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\MailChannelSender;

/**
 * Laravel glue for sage-counseling/helpers: publishes config/sage-helpers.php
 * and, on boot, wires up whatever channel senders a consuming app has
 * configured into ChannelRegistry (see docs/admin-notifications-contract.md).
 *
 * This is the only part of the package allowed to depend on illuminate/* —
 * the framework-agnostic core stays in SageCounseling\Helpers\Notifications.
 */
class SageHelpersServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__.'/../../config/sage-helpers.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'sage-helpers');
    }

    public function boot(): void
    {
        $this->publishes([
            self::CONFIG_PATH => $this->app->configPath('sage-helpers.php'),
        ], 'sage-helpers-config');

        $this->registerConfiguredSenders();
    }

    /**
     * Register a ChannelSender into ChannelRegistry for each channel that has
     * its required config present. Teams (#10) is still a no-op until that
     * sender lands.
     */
    protected function registerConfiguredSenders(): void
    {
        $config = $this->app->make('config')->get('sage-helpers', []);
        $adminEmail = $config['admin']['email'] ?? null;

        if ($adminEmail) {
            ChannelRegistry::register(Channel::Mail, new MailChannelSender(
                $this->app->make(Mailer::class),
                $adminEmail,
                $config['admin']['name'] ?? null,
            ));
        }

        // TODO(#10): if config('sage-helpers.teams.webhook_url') is set,
        // register a Teams ChannelSender for Channel::Teams.
    }
}
