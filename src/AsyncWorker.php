<?php

namespace Sbooker\EventLoopWorker;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AsyncWorker extends EventLoopWorker
{
    /**
     * @var array<AsyncProcessor>
     */
    private array $processors;
    public function __construct(array $processors, LoopInterface $loop, LoggerInterface $logger, ?string $name = null)
    {
        $this->processors = $processors;
        parent::__construct($loop, $logger, $name);
    }

    protected function configureEventLoop(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->processors as $processor) {
            $processor->withLoop($this->getLoop());
        }
    }
}