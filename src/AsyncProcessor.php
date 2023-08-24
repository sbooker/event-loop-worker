<?php

namespace Sbooker\EventLoopWorker;

use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\WorkableExecutor\Periodic;

final class AsyncProcessor
{
    private Workable $workable;
    private WorkableExecutor $executor;

    /**
     * @param Workable $workable
     * @param WorkableExecutor $executor
     */
    public function __construct(Workable $workable, WorkableExecutor $executor)
    {
        $this->workable = $workable;
        $this->executor = $executor;
    }

    public static function periodic(Workable $workable, float $timeout): self
    {
        return new self($workable, new Periodic($timeout));
    }

    public function withLoop(LoopInterface $loop): void
    {
        $this->executor->configureExecution($loop, $this->workable);
    }
}