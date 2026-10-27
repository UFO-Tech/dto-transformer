<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Closure;
use Ufo\DTO\Benchmarks\BenchmarkInterface;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Benchmarks\Fixture\FixtureAssertions;
use Ufo\DTO\DTOTransformer;

/**
 * Друга вісь: один root DTO, змінюється розмір кожної з п'яти вкладених колекцій.
 */
final readonly class NestingBench implements BenchmarkInterface
{
    private const int COLLECTIONS = 5;

    public function __construct(
        private bool $toArray = false,
    ) {}

    public function name(): string
    {
        return $this->toArray ? 'DTOTransformer::toArray by nested size' : 'DTOTransformer::fromArray by nested size';
    }

    public function steps(): array
    {
        return [1, 10, 100, 500];
    }

    public function units(int $step): int
    {
        return $step * self::COLLECTIONS;
    }

    public function unitName(): string
    {
        return 'nested item';
    }

    public function prepare(int $step): Closure
    {
        if (!$this->toArray) {
            $payload = BenchmarkPayloadFactory::rootArrayPayloadCollection(1, $step);
            $fixtureClass = BenchmarkPayloadFactory::fixtureClass();
            $namespaces = BenchmarkPayloadFactory::namespaces();

            return static fn (): array => [DTOTransformer::fromArray($fixtureClass, $payload[0], namespaces: $namespaces)];
        }

        $dto = BenchmarkPayloadFactory::rootDtoPayloadCollection(1, $step)[0];

        return static fn (): array => [DTOTransformer::toArray($dto)];
    }

    public function verify(int $step, mixed $result): void
    {
        $this->toArray
            ? FixtureAssertions::rootArrays($result, 1, $step)
            : FixtureAssertions::rootDtos($result, 1, $step);
    }
}
