<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

use Sbooker\Console\Command;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class EventLoopWorker extends Command
{
    private LoopInterface $loop;

    private LoggerInterface $logger;

    public function __construct(LoopInterface $loop, LoggerInterface $logger, ?string $name = null)
    {
        parent::__construct($name);
        $this->loop = $loop;
        $this->logger = $logger;
    }

    abstract protected function configureEventLoop(InputInterface $input, OutputInterface $output): void;

    /**
     * @throws \Throwable
     */
    final protected function doExecute(InputInterface $input, OutputInterface $output): void
    {
        $this->getLogger()->notice("Worker {$this->getName()} started.");

        $this->getLoop()->addSignal(SIGINT, function () {
            $this->doStop();
            $this->getLogger()->notice("Worker {$this->getName()} interrupted by user.");
        });

        $this->getLoop()->addSignal(SIGTERM, function () {
            $this->doStop();
            $this->getLogger()->notice("Worker {$this->getName()} terminated.");
        });

        $this->configureEventLoop($input, $output);

        try {
            $this->getLoop()->run();
        } catch (\Throwable $exception) {
            $this->doStop();
            throw $exception;
        }
    }

    protected function doStop(): void
    {
        $this->getLoop()->stop();
    }

    final protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    final protected function getLoop(): LoopInterface
    {
        return $this->loop;
    }
}