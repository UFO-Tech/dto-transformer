<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Growth;

use Closure;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Benchmarks\GrowthScenarioInterface;
use Ufo\DTO\DTOTransformer;

final class FixtureRoundTripGrowth implements GrowthScenarioInterface
{
    public function name(): string
    {
        return 'growth: fixture fromArray + toArray, fixed inputs';
    }

    public function prepare(): Closure
    {
        $payload = BenchmarkPayloadFactory::rootArrayPayloadCollection(1, BenchmarkPayloadFactory::NESTED_SIZE)[0];
        $fixtureClass = BenchmarkPayloadFactory::fixtureClass();
        $namespaces = BenchmarkPayloadFactory::namespaces();

        return static fn (int $i): array => DTOTransformer::toArray(
            DTOTransformer::fromArray($fixtureClass, $payload, namespaces: $namespaces),
        );
    }
}
