<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Closure;
use Ufo\DTO\Benchmarks\BenchmarkInterface;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Benchmarks\Fixture\FixtureAssertions;
use Ufo\DTO\Benchmarks\Fixture\TransformerGraph;

final readonly class DTOFromArrayTransformerBench implements BenchmarkInterface
{
    use RootDtoStepsTrait;

    public function __construct(
        private TransformerGraph $graph,
    ) {}

    public function name(): string
    {
        return 'DTOFromArrayTransformer::transform';
    }

    public function prepare(int $step): Closure
    {
        $payloads = BenchmarkPayloadFactory::rootArrayPayloadCollection($step, BenchmarkPayloadFactory::NESTED_SIZE);
        $fixtureClass = BenchmarkPayloadFactory::fixtureClass();
        $namespaces = BenchmarkPayloadFactory::namespaces();
        $graph = $this->graph;

        return static function () use ($payloads, $fixtureClass, $namespaces, $graph): array {
            $transformer = $graph->fromArrayTransformer();
            $dtos = [];
            foreach ($payloads as $payload) {
                $dtos[] = $transformer->transformFromArray($fixtureClass, $payload, namespaces: $namespaces);
            }

            return $dtos;
        };
    }

    public function verify(int $step, mixed $result): void
    {
        FixtureAssertions::rootDtos($result, $step);
    }
}
