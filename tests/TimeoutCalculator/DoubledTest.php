<?php

declare(strict_types=1);

namespace Test\Sbooker\EventLoopWorker\TimeoutCalculator;

use Sbooker\EventLoopWorker\TimeoutCalculator\Doubled;

class DoubledTest extends TestCase
{
    public function testInitial(): void
    {
        $initial = 3.0;
        $calculator = $this->createCalculator(1, 2, $initial);

        $current = $calculator->getCurrent();

        $this->assertEquals($initial, $current);
    }

    /**
     * @dataProvider minimalExamples
     */
    public function testMinimal(float $minimalTimeout, float $maximalTimeout, float $initialTimeout, array $steps): void
    {
        $calculator = $this->createCalculator($minimalTimeout, $maximalTimeout, $initialTimeout, $steps);

        $timeout = $calculator->calculate(true);

        $this->assertEquals($minimalTimeout, $timeout);
    }

    public function minimalExamples(): array
    {
        return [
            [ 0.1, 1.0, 0.5, [] ],
            [ 0.2, 1.0, 0.5, [ true ] ],
            [ 0.3, 1.0, 0.5, [ false ] ],
            [ 0.4, 1.0, 0.5, [ false, false ] ],
        ];
    }

    /**
     * @dataProvider maximalExamples
     */
    public function testMaximal(float $maximalTimeout, float $initialTimeout, int $steps): void
    {
        $calculator = $this->createCalculator(0.1, $maximalTimeout, $initialTimeout);
        for($i = 0; $i < $steps; $i++) {
            $calculator->calculate(false);
        }

        $timeout = $calculator->calculate(false);

        $this->assertEquals($maximalTimeout, $timeout);
    }

    public function maximalExamples(): array
    {
        return [
            [ 1.0, 0.1, 10 ],
            [ 1.0, 0.1, 3 ],
        ];
    }

    /**
     * @dataProvider gainExamples
     */
    public function testGain(float $initialTimeout, float $maximalTimeout, array $gainValues): void
    {
        $calculator = $this->createCalculator(0.1, $maximalTimeout, $initialTimeout);

        foreach ($gainValues as $gainValue) {
            $timeout = $calculator->calculate(false);
            $this->assertEquals($gainValue, $timeout);
        }
    }

    public function gainExamples(): array
    {
        return [
            [ 0.1, 1.0, [ 0.2, 0.4, 0.8, 1.0, 1.0 ] ],
            [ 0.2, 0.5, [ 0.4, 0.5, 0.5, 0.5 ] ],
        ];
    }

    private function createCalculator(float $minimalTimeout, float $maximalTimeout, float $initialTimeout, array $steps = []): Doubled
    {
        $calculator = new Doubled($minimalTimeout, $maximalTimeout, $initialTimeout);
        foreach ($steps as $isProcessed) {
            $calculator->calculate($isProcessed);
        }

        return $calculator;
    }
}