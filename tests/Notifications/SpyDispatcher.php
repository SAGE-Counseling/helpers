<?php

namespace SageCounseling\Helpers\Tests\Notifications;

use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Records dispatched jobs instead of queueing them. Implements the superset of
 * the Dispatcher contract across Laravel 9–13.
 */
final class SpyDispatcher implements Dispatcher
{
    /** @var list<object> */
    public array $dispatched = [];

    public function dispatch($command)
    {
        $this->dispatched[] = $command;
    }

    public function dispatchSync($command, $handler = null)
    {
    }

    public function dispatchNow($command, $handler = null)
    {
    }

    public function dispatchAfterResponse($command, $handler = null)
    {
    }

    public function chain($jobs = null)
    {
    }

    public function hasCommandHandler($command)
    {
        return false;
    }

    public function getCommandHandler($command)
    {
        return false;
    }

    public function pipeThrough(array $pipes)
    {
        return $this;
    }

    public function map(array $map)
    {
        return $this;
    }
}
