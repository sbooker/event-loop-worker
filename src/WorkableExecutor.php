<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\Workable;

interface WorkableExecutor
{
    public function configureExecution(LoopInterface $loop, Workable $workable): void;
}