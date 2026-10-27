<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use Closure;

interface GrowthScenarioInterface
{
    public function name(): string;

    /**
     * @return Closure(int): mixed
     */
    public function prepare(): Closure;
}
