<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker\TimeoutCalculator;

use Sbooker\EventLoopWorker\TimeoutCalculator;

final class Doubled implements TimeoutCalculator
{
    private float $minimalTimeout;

    private float $maximalTimeout;

    private float $currentTimeout;

    public function __construct(float $minimalTimeout = 0.1, float $maximalTimeout = 300.0, float $initialTimeout = 1.0)
    {
        assert($minimalTimeout > 0);
        assert($initialTimeout >= $minimalTimeout);
        assert($maximalTimeout > $minimalTimeout);

        $this->minimalTimeout = $minimalTimeout;
        $this->maximalTimeout = $maximalTimeout;
        $this->currentTimeout = $initialTimeout;
    }


    public function calculate(bool $isProcessed): float
    {
        if ($isProcessed) {
            $this->currentTimeout = $this->minimalTimeout;
        } else {
            $this->currentTimeout = 2 * $this->currentTimeout;
            if ($this->currentTimeout > $this->maximalTimeout) {
                $this->currentTimeout = $this->maximalTimeout;
            }
        }

        return $this->currentTimeout;
    }

    public function getCurrent(): float
    {
        return $this->currentTimeout;
    }
}