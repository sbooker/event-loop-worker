<?php

namespace Sbooker\EventLoopWorker;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\TimeoutCalculator\Doubled;
use Sbooker\EventLoopWorker\TimeoutCalculator\Permanent;
use Sbooker\EventLoopWorker\WorkableExecutor\Periodic;
use Sbooker\EventLoopWorker\WorkableExecutor\Timeouted;

final class WorkerFactory
{
    private LoopInterface $loop;
    private LoggerInterface $logger;

    public function __construct(LoopInterface $loop, LoggerInterface $logger)
    {
        $this->loop = $loop;
        $this->logger = $logger;
    }

    public function createPeriodic(Workable $workable, float $timeout): EventLoopWorker
    {
        return new AsyncWorker([new AsyncProcessor($workable, new Periodic($timeout))], $this->loop, $this->logger);
    }

    public function createPermanent(Workable $workable, float $timeout): EventLoopWorker
    {
        return $this->createTimeouted($workable, new Permanent($timeout));
    }

    public function createDoubled(Workable $workable, float $minimalTimeout = 0.1, float $maximalTimeout = 300.0, float $initialTimeout = 1.0): EventLoopWorker
    {
        return $this->createTimeouted($workable, new Doubled($minimalTimeout, $maximalTimeout, $initialTimeout));
    }

    private function createTimeouted(Workable $workable, TimeoutCalculator $timeoutCalculator): EventLoopWorker
    {
        return new AsyncWorker(
            [
                new AsyncProcessor($workable, new Timeouted($timeoutCalculator, $this->logger))
            ],
            $this->loop,
            $this->logger
        );
    }

    public function createDoubledWithPeriodic(Workable $doubled, Workable $periodic, float $minimalTimeout = 0.1, float $maximalTimeout = 300.0, float $initialTimeout = 1.0, float $periodicTimeout = 1.0): EventLoopWorker
    {
        return new AsyncWorker(
            [
                new AsyncProcessor($doubled, new Timeouted(new Doubled($minimalTimeout, $maximalTimeout, $initialTimeout), $this->logger)),
                new AsyncProcessor($periodic, new Periodic($periodicTimeout)),
            ],
            $this->loop,
            $this->logger
        );
    }
}