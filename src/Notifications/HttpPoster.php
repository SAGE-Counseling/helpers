<?php

namespace SageCounseling\Helpers\Notifications;

/**
 * Minimal HTTP-POST abstraction so channel senders (e.g. TeamsChannelSender)
 * don't depend directly on a specific HTTP client library's full interface.
 */
interface HttpPoster
{
    /**
     * @param  array<string, mixed>  $payload  Sent as the request's JSON body.
     */
    public function postJson(string $url, array $payload): void;
}
