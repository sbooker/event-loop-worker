<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker\WorkableExecutor;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\TimeoutCalculator;
use Sbooker\EventLoopWorker\WorkableExecutor;
use Sbooker\EventLoopWorker\Workable;

final class Timeouted implements WorkableExecutor
{
    private TimeoutCalculator $timeoutCalculator;

    private LoggerInterface $logger;

    public function __construct(TimeoutCalculator $timeoutCalculator, LoggerInterface $logger)
    {
        $this->timeoutCalculator = $timeoutCalculator;
        $this->logger = $logger;
    }

    public function configureExecution(LoopInterface $loop, Workable $workable): void
    {
        $loop->addTimer($this->getCurrentTimeout(), $this->getExecuteWorkable($loop, $workable));
    }

    private function executeWorkableAfter(LoopInterface $loop, float $timeout, Workable $workable): void
    {
        $loop->addTimer($timeout, $this->getExecuteWorkable($loop, $workable));
    }

    private function getExecuteWorkable(LoopInterface $loop, Workable $workable): callable
    {
        return fn () => $this->executeWorkable($loop, $workable);
    }

    private function executeWorkable(LoopInterface $loop, Workable $workable): void
    {
        $processResult = $workable->process();
        $timeout = $this->getNextTimeout($processResult);

        if ($processResult) {
            $this->getLogger()->debug("Processed! Set minimum timeout = {$timeout}");
        } else {
            $this->getLogger()->debug("Nothing to process! Set timeout = {$timeout}");
        }

        $this->executeWorkableAfter($loop, $timeout, $workable);
    }

    private function getCurrentTimeout(): float
    {
        return $this->timeoutCalculator->getCurrent();
    }

    private function getNextTimeout(bool $isProcessed): float
    {
        return $this->timeoutCalculator->calculate($isProcessed);
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}