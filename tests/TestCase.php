<?php

declare(strict_types=1);

namespace Test\Sbooker\EventLoopWorker;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

}

final class LoopStub implements LoopInterface
{
    private bool $isRunning = false;

    /**
     * @var callable[]
     */
    private array $signalsCallbacks = [];

    /**
     * @inheritDoc
     */
    public function addReadStream($stream, $listener)
    {
        // TODO: Implement addReadStream() method.
    }

    /**
     * @inheritDoc
     */
    public function addWriteStream($stream, $listener)
    {
        // TODO: Implement addWriteStream() method.
    }

    /**
     * @inheritDoc
     */
    public function removeReadStream($stream)
    {
        // TODO: Implement removeReadStream() method.
    }

    /**
     * @inheritDoc
     */
    public function removeWriteStream($stream)
    {
        // TODO: Implement removeWriteStream() method.
    }

    /**
     * @inheritDoc
     */
    public function addTimer($interval, $callback)
    {
        // TODO: Implement addTimer() method.
    }

    /**
     * @inheritDoc
     */
    public function addPeriodicTimer($interval, $callback)
    {
        // TODO: Implement addPeriodicTimer() method.
    }

    /**
     * @inheritDoc
     */
    public function cancelTimer(TimerInterface $timer)
    {
        // TODO: Implement cancelTimer() method.
    }

    /**
     * @inheritDoc
     */
    public function futureTick($listener)
    {
        // TODO: Implement futureTick() method.
    }

    /**
     * @inheritDoc
     */
    public function addSignal($signal, $listener)
    {
        $this->signalsCallbacks[$signal] = $listener;
    }

    /**
     * @inheritDoc
     */
    public function removeSignal($signal, $listener)
    {
        unset($this->signalsCallbacks[$signal]);
    }

    public function run()
    {
        $this->isRunning = true;
    }

    public function stop()
    {
        $this->isRunning = false;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }
}