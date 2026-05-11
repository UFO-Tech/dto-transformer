<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

readonly class BenchmarkResult
{
    public function __construct(
        public float $avg,
        public float $median,
        public float $min,
        public float $max,
    ) {}
}
