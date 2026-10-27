<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;

trait RootDtoStepsTrait
{
    public function steps(): array
    {
        return BenchmarkPayloadFactory::ROOT_STEPS;
    }

    public function units(int $step): int
    {
        return $step;
    }

    public function unitName(): string
    {
        return 'root DTO';
    }
}
