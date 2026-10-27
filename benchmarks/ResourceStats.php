<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final readonly class ResourceStats
{
    /**
     * @param array<string, float>|null $io bytes and operations per call
     */
    public function __construct(
        public float $cpuPerCall,
        public float $cpuPercent,
        public float $voluntarySwitches,
        public float $involuntarySwitches,
        public ?array $io,
    ) {}

    public static function fromTotals(ResourceUsage $total, float $wallMs, int $calls): self
    {
        $calls = max(1, $calls);

        return new self(
            cpuPerCall: $total->cpuMs / $calls,
            cpuPercent: $wallMs > 0.0 ? $total->cpuMs / $wallMs : 0.0,
            voluntarySwitches: $total->voluntarySwitches / $calls,
            involuntarySwitches: $total->involuntarySwitches / $calls,
            io: $total->io === null ? null : [
                'readPerCall' => max(0, $total->io['rchar']) / $calls,
                'writtenPerCall' => max(0, $total->io['wchar']) / $calls,
                'storageReadPerCall' => max(0, $total->io['read_bytes']) / $calls,
                'storageWrittenPerCall' => max(0, $total->io['write_bytes']) / $calls,
                'readOpsPerCall' => max(0, $total->io['syscr']) / $calls,
                'writeOpsPerCall' => max(0, $total->io['syscw']) / $calls,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cpuRow(): array
    {
        return [
            'perCall' => $this->cpuPerCall,
            'percent' => $this->cpuPercent,
            'voluntarySwitches' => $this->voluntarySwitches,
            'involuntarySwitches' => $this->involuntarySwitches,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows cpu rows of several processes
     * @return array<string, float>
     */
    public static function medianOf(array $rows): array
    {
        $median = [];
        foreach (array_keys($rows[0]) as $key) {
            $median[$key] = Stats::median(array_map(static fn (array $row): float => (float) $row[$key], $rows));
        }

        return $median;
    }
}
