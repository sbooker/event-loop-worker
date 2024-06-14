<?php

declare(strict_types=1);

namespace Sbooker\EventLoopWorker;

use Sbooker\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ProcessOneWithWorkable extends Command
{
    private Workable $workable;

    /**
     * @param Workable $workable
     */
    public function __construct(Workable $workable)
    {
        $this->workable = $workable;
        parent::__construct();
    }


    protected function doExecute(InputInterface $input, OutputInterface $output): void
    {
        $this->workable->process();
    }
}