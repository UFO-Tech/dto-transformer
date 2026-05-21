<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Ufo\DTO\Benchmarks\AbstractBenchmark;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\Interfaces\DTOToArrayTransformerInterface;

final class DTOToArrayTransformerBench extends AbstractBenchmark
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
        private readonly DTOToArrayTransformerInterface $transformer,
        mixed $clearRuntimeState = null,
    ) {
        parent::__construct($clearRuntimeState);
    }

    public function name(): string
    {
        return 'DTOToArrayTransformer::transform';
    }

    public function run(): void
    {
        foreach (self::DATASETS as $rootCount) {
            $this->benchPayload($rootCount);
        }
    }

    private function benchPayload(int $rootCount): void
    {
        $payloads = BenchmarkPayloadFactory::rootDtoPayloadCollection(
            rootCount: $rootCount,
            nestedSize: self::NESTED_SIZE,
        );
        $transformer = $this->transformer;

        $result = $this->measure(
            callback: static function () use ($payloads, $transformer): void {
                foreach ($payloads as $dto) {
                    $transformer->transformToArray($dto);
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
