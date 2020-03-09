<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\TimeoutCalculator\Doubled;
use Sbooker\EventLoopWorker\TimeoutCalculator\Permanent;
use Sbooker\EventLoopWorker\WorkableExecutor\Periodic;
use Sbooker\EventLoopWorker\WorkableExecutor\Timeouted;
use Sbooker\EventLoopWorker\TimedWorker;
use Sbooker\EventLoopWorker\Workable;

class TimedWorkerFactory
{
    private LoopInterface $loop;

    private LoggerInterface $logger;

    public function __construct(LoopInterface $loop, LoggerInterface $logger)
    {
        $this->loop = $loop;
        $this->logger = $logger;
    }

    public function createPeriodic(Workable $workable, float $timeout): TimedWorker
    {
        return new TimedWorker($workable, new Periodic($timeout), $this->loop, $this->logger);
    }

    public function createPermanent(Workable $workable, float $timeout): TimedWorker
    {
        return $this->createTimeouted($workable, new Permanent($timeout));
    }

    public function createDoubled(Workable $workable, float $minimalTimeout = 0.1, float $maximalTimeout = 300.0, float $initialTimeout = 1.0): TimedWorker
    {
        return $this->createTimeouted($workable, new Doubled($minimalTimeout, $maximalTimeout, $initialTimeout));
    }

    private function createTimeouted(Workable $workable, TimeoutCalculator $timeoutCalculator): TimedWorker
    {
        return new TimedWorker($workable, new Timeouted($timeoutCalculator, $this->logger), $this->loop, $this->logger);
    }
}