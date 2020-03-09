<?php
declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

interface TimeoutCalculator
{
    public function calculate(bool $isProcessed): float;

    public function getCurrent(): float;
}