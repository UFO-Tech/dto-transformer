<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Closure;
use Ufo\DTO\Benchmarks\BenchmarkInterface;
use Ufo\DTO\Benchmarks\Fixture\FixtureAssertions;
use Ufo\DTO\Benchmarks\Fixture\GeneratedClasses;
use Ufo\DTO\DTOTransformer;

/**
 * Кожен виклик проходить усі згенеровані класи кроку; великі кроки перевищують ліміти in-memory кешів.
 */
final readonly class PressureBench implements BenchmarkInterface
{
    public function __construct(
        private bool $toArray = false,
    ) {}

    public function name(): string
    {
        return $this->toArray ? 'pressure: toArray over N classes' : 'pressure: fromArray over N classes';
    }

    public function steps(): array
    {
        return [10, 100, 1000, 3000];
    }

    public function units(int $step): int
    {
        return $step;
    }

    public function unitName(): string
    {
        return 'class';
    }

    public function prepare(int $step): Closure
    {
        $classes = GeneratedClasses::ensure($step);
        $payloads = array_map(GeneratedClasses::payload(...), array_keys($classes));

        if (!$this->toArray) {
            return static function () use ($classes, $payloads): array {
                $dtos = [];
                foreach ($classes as $index => $class) {
                    $dtos[] = DTOTransformer::fromArray($class, $payloads[$index]);
                }

                return $dtos;
            };
        }

        $dtos = array_map(
            static fn (string $class, array $payload): object => DTOTransformer::fromArray($class, $payload),
            $classes,
            $payloads,
        );

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
        $this->toArray
            ? FixtureAssertions::generatedArrays($result, $step)
            : FixtureAssertions::generatedDtos($result, GeneratedClasses::ensure($step));
    }
}
