<?php

namespace SageCounseling\Helpers\Notifications;

use GuzzleHttp\ClientInterface;

/**
 * Guzzle-backed HttpPoster. Isolates the guzzlehttp/guzzle dependency to this
 * one adapter, so TeamsChannelSender itself only depends on the small
 * HttpPoster interface (easy to fake in tests).
 */
final class GuzzleHttpPoster implements HttpPoster
{
    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function postJson(string $url, array $payload): void
    {
        $this->client->request('POST', $url, ['json' => $payload]);
    }
}
