<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Growth;

use Closure;
use Ufo\DTO\Benchmarks\Fixture\DummyHolder;
use Ufo\DTO\Benchmarks\GrowthScenarioInterface;
use Ufo\DTO\DTOTransformer;

final class UniqueDefaultNamespaceGrowth implements GrowthScenarioInterface
{
    public function name(): string
    {
        return 'growth: unique $defaultNamespace';
    }

    public function prepare(): Closure
    {
        $payload = ['item' => ['id' => 1, 'name' => 'name']];

        return static fn (int $i): object => DTOTransformer::fromArray(
            DummyHolder::class,
            $payload,
            namespaces: ['$defaultNamespace' => 'Ns' . $i],
        );
    }
}
