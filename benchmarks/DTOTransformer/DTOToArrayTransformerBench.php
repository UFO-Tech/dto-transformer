<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Closure;
use Ufo\DTO\Benchmarks\BenchmarkInterface;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Benchmarks\Fixture\FixtureAssertions;
use Ufo\DTO\Benchmarks\Fixture\TransformerGraph;

final readonly class DTOToArrayTransformerBench implements BenchmarkInterface
{
    use RootDtoStepsTrait;

    public function __construct(
        private TransformerGraph $graph,
    ) {}

    public function name(): string
    {
        return 'DTOToArrayTransformer::transform';
    }

    public function prepare(int $step): Closure
    {
        $dtos = BenchmarkPayloadFactory::rootDtoPayloadCollection($step, BenchmarkPayloadFactory::NESTED_SIZE);
        $graph = $this->graph;

        return static function () use ($dtos, $graph): array {
            $transformer = $graph->toArrayTransformer();
            $arrays = [];
            foreach ($dtos as $dto) {
                $arrays[] = $transformer->transformToArray($dto);
            }

            return $arrays;
        };
    }

    public function verify(int $step, mixed $result): void
    {
        FixtureAssertions::rootArrays($result, $step);
    }
}
