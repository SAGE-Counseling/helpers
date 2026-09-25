<?php

namespace SageCounseling\Helpers\Laravel;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\GuzzleHttpPoster;
use SageCounseling\Helpers\Notifications\HttpPoster;
use SageCounseling\Helpers\Notifications\TeamsChannelSender;

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

        $this->app->singleton(HttpPoster::class, static fn () => new GuzzleHttpPoster(new Client()));
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
     * its required config present. Mail (#9) is a separate, still-pending
     * ticket's worth of wiring.
     */
    protected function registerConfiguredSenders(): void
    {
        // TODO(#9): if config('sage-helpers.admin.email') is set, register a
        // Mail ChannelSender for Channel::Mail.

        $webhookUrl = $this->app->make('config')->get('sage-helpers', [])['teams']['webhook_url'] ?? null;

        if ($webhookUrl) {
            ChannelRegistry::register(Channel::Teams, new TeamsChannelSender(
                $this->app->make(HttpPoster::class),
                $webhookUrl,
            ));
        }
    }
}
