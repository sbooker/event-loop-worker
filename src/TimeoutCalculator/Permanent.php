<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker\TimeoutCalculator;

use Sbooker\EventLoopWorker\TimeoutCalculator;

final class Permanent implements TimeoutCalculator
{
    private float $timeout;

    public function __construct(float $timeout)
    {
        $this->timeout = $timeout;
    }

    public function calculate(bool $isProcessed): float
    {
        return $this->timeout;
    }

    public function getCurrent(): float
    {
        return $this->timeout;
    }
}