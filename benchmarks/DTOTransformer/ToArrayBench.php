<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Closure;
use Ufo\DTO\Benchmarks\BenchmarkInterface;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Benchmarks\Fixture\FixtureAssertions;
use Ufo\DTO\DTOTransformer;

final class ToArrayBench implements BenchmarkInterface
{
    use RootDtoStepsTrait;

    public function name(): string
    {
        return 'DTOTransformer::toArray';
    }

    public function prepare(int $step): Closure
    {
        $dtos = BenchmarkPayloadFactory::rootDtoPayloadCollection($step, BenchmarkPayloadFactory::NESTED_SIZE);

        return static function () use ($dtos): array {
            $arrays = [];
            foreach ($dtos as $dto) {
                $arrays[] = DTOTransformer::toArray($dto);
            }

            return $arrays;
        };
    }

    public function verify(int $step, mixed $result): void
    {
        FixtureAssertions::rootArrays($result, $step);
    }
}
