<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

require_once dirname(__DIR__) . '/vendor/autoload.php';

readonly class BenchmarkRunner
{
    private const RESET = "\033[0m";
    private const CYAN = "\033[36m";
    private const GRAY = "\033[90m";

    /**
     * @param BenchmarkInterface[] $benchmarks
     */
    public function __construct(
        private array $benchmarks,
    ) {}

    public function run(): void
    {
        foreach ($this->benchmarks as $benchmark) {
            echo PHP_EOL;

            echo self::CYAN;
            echo str_repeat('=', 60) . PHP_EOL;
            echo ' ' . $benchmark->name() . PHP_EOL;
            echo str_repeat('=', 60) . PHP_EOL;
            echo self::RESET;

            echo self::GRAY;
            printf("%-18s %-12s %-12s %-12s %-12s\n", 'Scenario', 'Avg', 'Median', 'Min', 'Max');

            echo str_repeat('-', 60) . PHP_EOL;
            echo self::RESET;

            $benchmark->run();

            echo PHP_EOL;
        }
    }
}
