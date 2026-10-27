<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Closure;
use Ufo\DTO\Benchmarks\BenchmarkInterface;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Benchmarks\Fixture\FixtureAssertions;
use Ufo\DTO\DTOTransformer;

final class FromArrayBench implements BenchmarkInterface
{
    use RootDtoStepsTrait;

    public function name(): string
    {
        return 'DTOTransformer::fromArray';
    }

    public function prepare(int $step): Closure
    {
        $payloads = BenchmarkPayloadFactory::rootArrayPayloadCollection($step, BenchmarkPayloadFactory::NESTED_SIZE);
        $fixtureClass = BenchmarkPayloadFactory::fixtureClass();
        $namespaces = BenchmarkPayloadFactory::namespaces();

        return static function () use ($payloads, $fixtureClass, $namespaces): array {
            $dtos = [];
            foreach ($payloads as $payload) {
                $dtos[] = DTOTransformer::fromArray($fixtureClass, $payload, namespaces: $namespaces);
            }

            return $dtos;
        };
    }

    public function verify(int $step, mixed $result): void
    {
        FixtureAssertions::rootDtos($result, $step);
    }
}
