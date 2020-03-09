<?php

declare(strict_types=1);

namespace Test\Sbooker\EventLoopWorker\TimeoutCalculator;

use Sbooker\EventLoopWorker\TimeoutCalculator\Permanent;

final class PermanentTest extends TestCase
{
    /**
     * @dataProvider examples
     */
    public function test(float $timeout, array $steps, bool $isProcessed): void
    {
        $calculator = $this->createCalculator($timeout, $steps);

        $current = $calculator->getCurrent();
        $calculated = $calculator->calculate($isProcessed);

        $this->assertEquals($timeout, $current);
        $this->assertEquals($timeout, $calculated);
    }

    public function examples(): array
    {
        return [
            [ 0.1, [ ], true ],
            [ 0.2, [ ], false ],
            [ 0.3, [ false ], false ],
            [ 0.4, [ false ], true ],
            [ 0.5, [ true  ], false ],
            [ 0.6, [ true  ], true ],
            [ 0.7, [ true, false, false, true ], false ],
            [ 11.12, [ true, false, false, true  ], true ],
        ];
    }

    private function createCalculator(float $timeout, array $steps): Permanent
    {
        $calculator = new Permanent($timeout);
        foreach ($steps as $isProcessed) {
            $calculator->calculate($isProcessed);
        }

        return $calculator;
    }
}