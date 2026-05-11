<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

interface BenchmarkInterface
{
    public function name(): string;

    public function run(): void;
}