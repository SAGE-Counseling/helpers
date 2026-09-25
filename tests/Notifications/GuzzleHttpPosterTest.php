<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Notifications\GuzzleHttpPoster;

class GuzzleHttpPosterTest extends TestCase
{
    public function test_posts_json_to_the_given_url(): void
    {
        $client = new FakeGuzzleClient();
        $poster = new GuzzleHttpPoster($client);

        $poster->postJson('https://example.webhook.office.com/webhookb2/abc', ['text' => 'hello']);

        $this->assertCount(1, $client->requests);
        $this->assertSame('POST', $client->requests[0]['method']);
        $this->assertSame('https://example.webhook.office.com/webhookb2/abc', $client->requests[0]['uri']);
        $this->assertSame(['text' => 'hello'], $client->requests[0]['options']['json']);
    }
}
