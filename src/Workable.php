<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

interface Workable
{
    /**
     * @return bool true if processed, false otherwise
     */
    public function process(): bool;
}