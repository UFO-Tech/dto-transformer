<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final readonly class TimeStats
{
    /**
     * @param list<float> $samples milliseconds per call
     */
    public function __construct(
        public array $samples,
        public int $callsPerSample,
        public float $median,
        public float $min,
        public float $max,
        public float $noise,
    ) {}

    /**
     * @param list<float> $samples
     */
    public static function fromSamples(array $samples, int $callsPerSample): self
    {
        $median = Stats::median($samples);
        $iqr = Stats::percentile($samples, 75.0) - Stats::percentile($samples, 25.0);

        return new self(
            samples: $samples,
            callsPerSample: $callsPerSample,
            median: $median,
            min: min($samples),
            max: max($samples),
            noise: $median > 0.0 ? $iqr / $median : 0.0,
        );
    }
}
