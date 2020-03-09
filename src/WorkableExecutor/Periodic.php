<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker\WorkableExecutor;

use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\WorkableExecutor;
use Sbooker\EventLoopWorker\Workable;

final class Periodic implements WorkableExecutor
{
    private float $timeout;

    public function __construct(float $timeout)
    {
        $this->timeout = $timeout;
    }

    public function configureExecution(LoopInterface $loop, Workable $workable): void
    {
        $loop->addPeriodicTimer($this->timeout, fn () => $workable->process());
    }
}