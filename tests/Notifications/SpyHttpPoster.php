<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use SageCounseling\Helpers\Notifications\HttpPoster;

final class SpyHttpPoster implements HttpPoster
{
    /** @var list<array{url: string, payload: array<string, mixed>}> */
    public array $calls = [];

    public function postJson(string $url, array $payload): void
    {
        $this->calls[] = ['url' => $url, 'payload' => $payload];
    }
}
