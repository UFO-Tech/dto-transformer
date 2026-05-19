<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\DTOTransformer;

use Ufo\DTO\Benchmarks\AbstractBenchmark;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkPayloadFactory;
use Ufo\DTO\DTOTransformer;

final class ToArrayBench extends AbstractBenchmark
{
    protected const float GOOD_THRESHOLD = 0.1;
    protected const float WARNING_THRESHOLD = 0.5;

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

    public function name(): string
    {
        return 'DTOTransformer::toArray';
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

        $result = $this->measure(
            callback: static function () use ($payloads): void {
                foreach ($payloads as $dto) {
                    DTOTransformer::toArray($dto);
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
