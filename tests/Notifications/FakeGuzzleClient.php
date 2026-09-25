<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal stand-in for GuzzleHttp\ClientInterface, covering only the
 * request() call GuzzleHttpPoster actually makes.
 */
final class FakeGuzzleClient implements ClientInterface
{
    /** @var list<array{method: string, uri: string, options: array<string, mixed>}> */
    public array $requests = [];

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        throw new \LogicException('FakeGuzzleClient::send() is not used by GuzzleHttpPoster.');
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        throw new \LogicException('FakeGuzzleClient::sendAsync() is not used by GuzzleHttpPoster.');
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        $this->requests[] = ['method' => $method, 'uri' => (string) $uri, 'options' => $options];

        return new Response();
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        throw new \LogicException('FakeGuzzleClient::requestAsync() is not used by GuzzleHttpPoster.');
    }

    public function getConfig(?string $option = null)
    {
        return null;
    }
}
