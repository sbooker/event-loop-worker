[Read in Russian](README.md)

# Event Loop Worker (`sbooker/event-loop-worker`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

An asynchronous, production-ready worker based on [ReactPHP EventLoop](https://reactphp.org/event-loop/).

## Library's Purpose

### The Problem: Naive Workers

Two common approaches are often used for background task processing, and both have serious drawbacks:

**1. The "Infinite" `while`-loop with `sleep()`:**
```php
while (true) {
    if (!$handler->handleNext()) {
        sleep(5);
    }
}
```
*   **Drawbacks:** Inefficient polling, blocking operations, fixed delay.

**2. A command launched by `cron`:**
```php
// Runs every minute via cron
while ($handler->handleNext()) { /* ... */ }
```
*   **Drawbacks:**
    *   **High Latency:** A new task will wait up to a minute before its processing begins.
    *   **Peak Loads:** Creates a peak load on resources at the beginning of each minute.
    *   **Concurrent Run Issues:** If processing takes longer than a minute, the next `cron` process might start in parallel.

Attempts to "fix" `cron` with **mutexes (locking mechanisms)** to prevent parallel runs only create new problems:
*   **File-based locking:** Works poorly in multi-server or containerized environments.
*   **Redis-based locking:** Requires deploying and maintaining additional infrastructure.
*   **DB-based locking:** Creates extra load on the database and can lead to deadlocks.

### The Solution: A Smart Event Loop Worker

This library solves all these problems by providing a **continuously running (daemonized) worker** based on an event loop. It is launched once and runs indefinitely.

**The main feature** is the `Doubled` strategy (exponential backoff). The worker:
1.  Frequently checks for new tasks when they are available (e.g., every 0.1s).
2.  If there are no tasks, it **exponentially increases** the waiting interval (e.g., 1s, 2s, 4s, 8s...) up to a defined maximum.
3.  As soon as a new task appears and is processed, the interval resets to the minimum.

This allows for the creation of a **highly responsive and at the same time very resource-efficient** worker.

## Key Features

*   **Asynchronous Foundation:** Built on `react/event-loop`, ensuring non-blocking operation.
*   **Smart Wait Strategies:** Includes `Periodic` (constant interval) and `Doubled` (exponential backoff) to save resources.
*   **Graceful Shutdown:** Catches `SIGINT` and `SIGTERM` signals for a clean shutdown.
*   **Composability:** Allows running **multiple different handlers** (e.g., a primary one and a ping to keep a DB connection alive) within a single worker.
*   **Simple Integration:** Any of your handlers can be adapted via the simple `Workable` interface.

## Installation

```bash
composer require sbooker/event-loop-worker
```
**Dependencies:**
*   `react/event-loop`

## Quick Start

### Step 1: Adapt Your Handler

You need a class that implements the `Workable` interface. The `sbooker/command-bus` library already provides a ready-to-use `CommandProcessor` adapter.

```php
// bootstrap.php
use Sbooker\CommandBus\Handler;
use Sbooker\CommandBus\Infrastructure\Worker\CommandProcessor;

/** @var Handler $commandHandler */ // Your handler from command-bus

// Simply wrap it in a Workable adapter
$workable = new CommandProcessor($commandHandler);
```

### Step 2: Create the Worker Console Command

Create a class that extends `Symfony\Component\Console\Command` and use the `WorkerFactory` to configure it.

```php
// src/Command/RunWorkerCommand.php
use Sbooker\EventLoopWorker\WorkerFactory;
use Sbooker\EventLoopWorker\Workable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunWorkerCommand extends Command
{
    private WorkerFactory $workerFactory;
    private Workable $workable; // Your adapted handler

    // ... constructor ...

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create a worker with exponential backoff.
        // This is the most recommended option.
        $worker = $this->workerFactory->createDoubled(
            $this->workable,
            0.1,   // Minimum timeout (when there is work)
            300.0, // Maximum timeout (when there is no work for a long time)
            1.0    // Initial timeout
        );

        // Run the infinite loop
        return $worker->run($input, $output);
    }
}
```

### Step 3: Run the Worker

Now you can run your worker from the console.
```bash
php bin/console app:run-worker
```

## Advanced Usage

### Solving the "Connection timed out" Problem with PostgreSQL

Long-running processes that work with PostgreSQL often face the issue of the connection being dropped if there are no queries for a while.

The `createDoubledWithPeriodic` method is the perfect solution to this problem. You can use it to run a very lightweight periodic task (e.g., `SELECT 1`) alongside your main task to keep the connection alive.

**1. Create a "pinging" `Workable`:**
```php
// src/Infrastructure/Persistence/ConnectionPinger.php
use Doctrine\DBAL\Connection;
use Sbooker\EventLoopWorker\Workable;

final class ConnectionPinger implements Workable
{
    private Connection $connection;
    public function __construct(Connection $connection) { $this->connection = $connection; }

    public function process(): bool
    {
        $this->connection->executeQuery('SELECT 1');
        return true; // Always return true, as the "work" was done
    }
}
```

**2. Use it in the worker:**
```php
// src/Command/RunWorkerCommand.php

/** @var Workable $commandProcessor */
/** @var ConnectionPinger $pinger */

$worker =
    $this->workerFactory->createDoubledWithPeriodic(
        $commandProcessor, // The main task
        $pinger,           // The task to "ping" the connection
        0.1,
        300.0,
        1.0,
        30.0 // "Ping" the connection every 30 seconds
    );

return $worker->run($input, $output);
```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/event-loop-worker.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/event-loop-worker.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/event-loop-worker.svg?style=flat-square

[release]: https://packagist.org/packages/sbooker/event-loop-worker
[license]: https://github.com/sbooker/event-loop-worker/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/event-loop-worker
