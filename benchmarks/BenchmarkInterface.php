<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use Closure;
use RuntimeException;

interface BenchmarkInterface
{
    public function name(): string;

    /**
     * @return list<int>
     */
    public function steps(): array;

    public function units(int $step): int;

    public function unitName(): string;

    /**
     * @return Closure(): mixed
     */
    public function prepare(int $step): Closure;

    /**
     * @throws RuntimeException
     */
    public function verify(int $step, mixed $result): void;
}
