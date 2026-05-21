<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Ufo\DTO\Benchmarks\AbstractBenchmark;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;

final class DTOFromArrayTransformerBench extends AbstractBenchmark
{
    /**
     * @var int[]
     */
    private const array DATASETS = [
        1,
        10,
        50,
        100,
        250,
        500,
    ];

    private const int NESTED_SIZE = 5;

    public function __construct(
        private readonly DTOFromArrayTransformerInterface $transformer,
        mixed $clearRuntimeState = null,
    ) {
        parent::__construct($clearRuntimeState);
    }

    public function name(): string
    {
        return 'DTOFromArrayTransformer::transform';
    }

    public function run(): void
    {
        foreach (self::DATASETS as $rootCount) {
            $this->benchPayload($rootCount);
        }
    }

    private function benchPayload(int $rootCount): void
    {
        $payloads = BenchmarkPayloadFactory::rootArrayPayloadCollection(
            rootCount: $rootCount,
            nestedSize: self::NESTED_SIZE,
        );
        $fixtureClass = BenchmarkPayloadFactory::fixtureClass();
        $namespaces = BenchmarkPayloadFactory::namespaces();
        $transformer = $this->transformer;

        $result = $this->measure(
            callback: static function () use ($payloads, $fixtureClass, $namespaces, $transformer): void {
                foreach ($payloads as $payload) {
                    $transformer->transformFromArray(
                        $fixtureClass,
                        $payload,
                        namespaces: $namespaces,
                    );
                }
            },
        );
        $perDto = $result->avg / $rootCount;

        $this->printResult(
            scenario: sprintf('%d root DTO', $rootCount),
            result: $result,
            extra: [
                'Per DTO' => $this->formatMs($perDto),
                'Ops/sec' => $this->formatNumber(1000 / $perDto),
            ],
            colorizeBy: $perDto,
        );
    }
}
