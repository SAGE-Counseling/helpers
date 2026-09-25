<?php

namespace SageCounseling\Helpers\Laravel;

use Illuminate\Support\ServiceProvider;

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
     * its required config present. No concrete senders exist yet (see issues
     * #2/#3 for Mail and Teams) — this is a no-op today, to be filled in as
     * those senders land.
     */
    protected function registerConfiguredSenders(): void
    {
        // TODO(#2): if config('sage-helpers.admin.email') is set, register a
        // Mail ChannelSender for Channel::Mail.
        // TODO(#3): if config('sage-helpers.teams.webhook_url') is set,
        // register a Teams ChannelSender for Channel::Teams.
    }
}
