<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use Closure;
use LogicException;

final readonly class Measurer
{
    public function __construct(
        private ?Closure $reset = null,
    ) {}

    public function calibrate(Closure $run, float $targetMs): int
    {
        if ($this->reset !== null) {
            return 1;
        }

        $run();
        $startedAt = hrtime(true);
        $run();
        $single = (hrtime(true) - $startedAt) / 1e6;

        return max(1, (int) ceil($targetMs / max($single, 1e-6)));
    }

    /**
     * @return array{TimeStats, ResourceStats}
     */
    public function time(Closure $run, int $samples, int $callsPerSample): array
    {
        if ($this->reset !== null && $callsPerSample !== 1) {
            throw new LogicException('Reset mode measures exactly one call per sample');
        }

        $run();
        $overhead = $this->usageOverhead();
        $used = ResourceUsage::zero();
        $durations = [];

        for ($sample = 0; $sample < $samples; $sample++) {
            $this->reset();
            $before = ResourceUsage::now();
            $startedAt = hrtime(true);

            for ($call = 0; $call < $callsPerSample; $call++) {
                $run();
            }

            $elapsed = hrtime(true) - $startedAt;
            $used = $used->plus(ResourceUsage::now()->minus($before)->minus($overhead));
            $durations[] = $elapsed / 1e6 / $callsPerSample;
        }

        return [
            TimeStats::fromSamples($durations, $callsPerSample),
            ResourceStats::fromTotals($used, array_sum($durations) * $callsPerSample, $samples * $callsPerSample),
        ];
    }

    public function memory(Closure $run, int $samples): MemoryStats
    {
        $run();
        [$allocatedOverhead, $retainedOverhead] = $this->overhead();
        $allocated = [];
        $retained = [];

        for ($sample = 0; $sample < $samples; $sample++) {
            [$bytes, $left] = $this->memoryOfOneCall($run);
            $allocated[] = $bytes - $allocatedOverhead;
            $retained[] = $left - $retainedOverhead;
        }

        return MemoryStats::fromSamples($allocated, $retained);
    }

    /**
     * @param Closure(mixed): void $verify
     * @return array{float, int, int, ResourceStats} milliseconds, allocated bytes, retained bytes, CPU and I/O
     */
    public function firstCall(Closure $run, Closure $verify): array
    {
        [$allocatedOverhead, $retainedOverhead] = $this->overhead();
        $usageOverhead = $this->usageOverhead();
        $this->reset();
        gc_collect_cycles();
        $before = ResourceUsage::now();
        memory_reset_peak_usage();
        $base = memory_get_usage();
        $startedAt = hrtime(true);

        $result = $run();

        $milliseconds = (hrtime(true) - $startedAt) / 1e6;
        $allocated = memory_get_peak_usage() - $base;
        $used = ResourceUsage::now()->minus($before)->minus($usageOverhead);
        $verify($result);
        unset($result);
        gc_collect_cycles();

        return [
            $milliseconds,
            $allocated - $allocatedOverhead,
            memory_get_usage() - $base - $retainedOverhead,
            ResourceStats::fromTotals($used, $milliseconds, 1),
        ];
    }

    /**
     * @param Closure(int): mixed $call
     */
    public function growth(Closure $call, int $rounds, int $callsPerRound, float $toleranceBytesPerCall): GrowthStats
    {
        $call(0);
        gc_collect_cycles();
        $base = memory_get_usage();
        $points = [];
        $index = 1;

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $callsPerRound; $i++) {
                $call($index++);
            }

            gc_collect_cycles();
            $points[] = memory_get_usage() - $base;
        }

        return GrowthStats::fromRounds($points, $callsPerRound, $toleranceBytesPerCall);
    }

    private function usageOverhead(): ResourceUsage
    {
        $before = ResourceUsage::now();

        return ResourceUsage::now()->minus($before)->ioOnly();
    }

    /**
     * @return array{int, int}
     */
    private function overhead(): array
    {
        $empty = static fn (): null => null;
        $allocated = [];
        $retained = [];

        for ($sample = 0; $sample < 3; $sample++) {
            [$allocated[], $retained[]] = $this->memoryOfOneCall($empty, false);
        }

        return [min($allocated), min($retained)];
    }

    /**
     * @return array{int, int}
     */
    private function memoryOfOneCall(Closure $run, bool $reset = true): array
    {
        if ($reset) {
            $this->reset();
        }
        gc_collect_cycles();
        memory_reset_peak_usage();
        $base = memory_get_usage();

        $result = $run();

        $allocated = memory_get_peak_usage() - $base;
        unset($result);
        gc_collect_cycles();

        return [$allocated, memory_get_usage() - $base];
    }

    private function reset(): void
    {
        if ($this->reset !== null) {
            ($this->reset)();
        }
    }
}
