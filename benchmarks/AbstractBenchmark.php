<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

abstract class AbstractBenchmark implements BenchmarkInterface
{
    private const string RESET = "\033[0m";

    private const string GREEN = "\033[32m";
    private const string YELLOW = "\033[33m";
    private const string RED = "\033[31m";

    protected const int DEFAULT_ITERATIONS = 20;

    protected const float GOOD_THRESHOLD = 50.0;
    protected const float WARNING_THRESHOLD = 500.0;

    public function __construct(
        private readonly mixed $clearRuntimeState = null,
    ) {}

    protected function measure(
        callable $callback,
        int $iterations = self::DEFAULT_ITERATIONS,
    ): BenchmarkResult
    {
        $this->clearRuntimeState();
        $callback();
        $this->clearRuntimeState();

        $durations = [];

        for ($i = 0; $i < $iterations; $i++) {
            $this->clearRuntimeState();
            $startedAt = hrtime(true);

            $callback();

            $durations[] = (hrtime(true) - $startedAt) / 1_000_000;
            $this->clearRuntimeState();
        }

        sort($durations);

        $count = count($durations);
        $middle = (int) floor($count / 2);

        return new BenchmarkResult(
            avg: array_sum($durations) / $count,
            median: $count % 2 === 0
                ? ($durations[$middle - 1] + $durations[$middle]) / 2
                : $durations[$middle],
            min: $durations[0],
            max: $durations[$count - 1],
        );
    }

    /**
     * @param array<string, string> $extra
     */
    protected function printResult(
        string $scenario,
        BenchmarkResult $result,
        array $extra = [],
        ?float $colorizeBy = null,
    ): void {
        $color = $this->colorize($colorizeBy ?? $result->avg);

        printf(
            "%-18s %s%-12s %-12s %-12s %-12s%s",
            $scenario,
            $color,
            $this->formatMs($result->avg),
            $this->formatMs($result->median),
            $this->formatMs($result->min),
            $this->formatMs($result->max),
            self::RESET,
        );

        foreach ($extra as $label => $value) {
            printf(' | %s: %s', $label, $value);
        }

        echo PHP_EOL;
    }

    protected function formatMs(float $value): string
    {
        return sprintf('%.2f ms', $value);
    }

    protected function formatNumber(float $value): string
    {
        return sprintf('%.1f', $value);
    }

    protected function colorize(float $value): string
    {
        return match (true) {
            $value < static::GOOD_THRESHOLD => self::GREEN,
            $value < static::WARNING_THRESHOLD => self::YELLOW,
            default => self::RED,
        };
    }

    private function clearRuntimeState(): void
    {
        if ($this->clearRuntimeState === null) {
            return;
        }

        ($this->clearRuntimeState)();
    }
}
