[Read in English](README.EN.md)

# Event Loop Worker (`sbooker/event-loop-worker`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

Асинхронный, production-ready воркер на базе [ReactPHP EventLoop](https://reactphp.org/event-loop/).

## Назначение библиотеки

### Проблема: наивные воркеры

Для фоновой обработки задач часто используются два подхода, и оба имеют серьезные недостатки:

**1. "Вечный" `while`-цикл с `sleep()`:**
```php
while (true) {
    if (!$handler->handleNext()) {
        sleep(5);
    }
}
```
*   **Недостатки:** Неэффективный поллинг, блокирующие операции, фиксированная задержка.

**2. Команда, запускаемая по `cron`:**
```php
// Запускается каждую минуту по cron
while ($handler->handleNext()) { /* ... */ }
```
*   **Недостатки:**
    *   **Высокая задержка:** Новая задача будет ждать до минуты, прежде чем ее обработка начнется.
    *   **Пиковые нагрузки:** Создает пиковую нагрузку на ресурсы в начале каждой минуты.
    *   **Проблемы с параллельным запуском:** Если обработка занимает больше минуты, следующий `cron`-процесс может запуститься параллельно.

Попытки "починить" `cron` с помощью **мьютексов (механизмов блокировки)** для предотвращения параллельного запуска лишь создают новые проблемы:
*   **Блокировка на файлах:** Плохо работает в многосерверной или контейнеризированной среде.
*   **Блокировка в Redis:** Требует развертывания и поддержки дополнительной инфраструктуры.
*   **Блокировка в БД:** Создает дополнительную нагрузку на базу данных и может приводить к дедлокам.

### Решение: умный воркер на Event Loop

Эта библиотека решает все эти проблемы, предоставляя **постоянно работающий (демонизированный) воркер** на базе event loop. Он запускается один раз и работает непрерывно.

**Главная особенность** — стратегия `Doubled` (экспоненциальная задержка). Воркер:
1.  Часто проверяет наличие новых задач, когда они есть (например, каждые 0.1с).
2.  Если задач нет, он **экспоненциально увеличивает** интервал ожидания (например, 1с, 2с, 4с, 8с...) до заданного максимума.
3.  Как только новая задача появляется и обрабатывается, интервал снова сбрасывается до минимального.

Это позволяет создать **высокоотзывчивый и одновременно очень экономичный** воркер.

## Ключевые особенности

*   **Асинхронная основа:** Построен на `react/event-loop`, что обеспечивает неблокирующую работу.
*   **Умные стратегии ожидания:** Включает `Periodic` (постоянный интервал) и `Doubled` (экспоненциальная задержка) для экономии ресурсов.
*   **Корректное завершение работы:** Перехватывает сигналы `SIGINT` и `SIGTERM` для graceful shutdown.
*   **Компонуемость:** Позволяет запускать **несколько разных обработчиков** (например, основной и ping для поддержания открытого соединения с БД) в рамках одного воркера.
*   **Простая интеграция:** Любой ваш обработчик может быть адаптирован через простой интерфейс `Workable`.

## Установка

```bash
composer require sbooker/event-loop-worker
```
**Зависимости:**
*   `react/event-loop`

## Быстрый старт

### Шаг 1: Адаптируйте ваш обработчик

Вам нужен класс, реализующий интерфейс `Workable`. Библиотека `sbooker/command-bus` уже предоставляет готовый адаптер `CommandProcessor`.

```php
// bootstrap.php
use Sbooker\CommandBus\Handler;
use Sbooker\CommandBus\Infrastructure\Worker\CommandProcessor;

/** @var Handler $commandHandler */ // Ваш обработчик из command-bus

// Просто оборачиваем его в Workable-адаптер
$workable = new CommandProcessor($commandHandler);
```

### Шаг 2: Создайте консольную команду воркера

Создайте класс, унаследованный от `Symfony\Component\Console\Command`, и используйте `WorkerFactory` для его конфигурации.

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
    private Workable $workable; // Ваш адаптированный обработчик

    // ... constructor ...

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Создаем воркер с экспоненциальной задержкой.
        // Это наиболее рекомендуемый вариант.
        $worker = $this->workerFactory->createDoubled(
            $this->workable,
            0.1,   // Минимальный таймаут (когда есть работа)
            300.0, // Максимальный таймаут (когда работы долго нет)
            1.0    // Начальный таймаут
        );

        // Запускаем вечный цикл
        return $worker->run($input, $output);
    }
}
```

### Шаг 3: Запустите воркер

Теперь вы можете запустить ваш воркер из консоли.
```bash
php bin/console app:run-worker
```

## Продвинутое использование

### Решение проблемы "Connection timed out" с PostgreSQL

Долгоживущие процессы, работающие с PostgreSQL, часто сталкиваются с проблемой разрыва соединения, если в течение некоторого времени нет запросов.

Метод `createDoubledWithPeriodic` идеально решает эту проблему. Вы можете использовать его, чтобы наряду с основной задачей запускать очень легкую периодическую задачу (например, `SELECT 1`), которая будет поддерживать соединение "живым".

**1. Создайте "пингующий" `Workable`:**
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
        return true; // Всегда возвращаем true, т.к. "работа" была выполнена
    }
}
```

**2. Используйте его в воркере:**
```php
// src/Command/RunWorkerCommand.php

/** @var Workable $commandProcessor */
/** @var ConnectionPinger $pinger */

$worker =
    $this->workerFactory->createDoubledWithPeriodic(
        $commandProcessor, // Основная задача
        $pinger,           // Задача для "пинга" соединения
        0.1,
        300.0,
        1.0,
        30.0 // "Пинговать" соединение каждые 30 секунд
    );

return $worker->run($input, $output);
```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/event-loop-worker.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/event-loop-worker.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/event-loop-worker.svg?style=flat-square

[release]: https://img.shields.io/packagist/v/sbooker/event-loop-worker
[license]: https://github.com/sbooker/event-loop-worker/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/event-loop-worker
