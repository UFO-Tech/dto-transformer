<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final readonly class MemoryStats
{
    /**
     * @param list<int> $allocated bytes, peak of one call
     * @param list<int> $retained bytes left after one call and gc_collect_cycles()
     */
    public function __construct(
        public array $allocated,
        public array $retained,
        public float $allocatedMedian,
        public float $retainedMedian,
    ) {}

    /**
     * @param list<int> $allocated
     * @param list<int> $retained
     */
    public static function fromSamples(array $allocated, array $retained): self
    {
        return new self(
            allocated: $allocated,
            retained: $retained,
            allocatedMedian: Stats::median($allocated),
            retainedMedian: Stats::median($retained),
        );
    }
}
