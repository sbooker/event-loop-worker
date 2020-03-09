<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Sbooker\EventLoopWorker\WorkableExecutor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TimedWorker extends EventLoopWorker
{
    private Workable $workable;

    private WorkableExecutor $executor;

    public function __construct(
        Workable $workable,
        WorkableExecutor $executor,
        LoopInterface $loop,
        LoggerInterface $logger,
        ?string $name = null
    )
    {
        parent::__construct($loop, $logger, $name);
        $this->workable = $workable;
        $this->executor = $executor;
    }

    protected function configureEventLoop(InputInterface $input, OutputInterface $output): void
    {
        $this->executor->configureExecution($this->getLoop(), $this->getWorkable());
    }

    private function getWorkable(): Workable
    {
        return $this->workable;
    }
}