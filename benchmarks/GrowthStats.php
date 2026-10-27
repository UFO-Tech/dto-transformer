<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final readonly class GrowthStats
{
    private const int SPREAD_WINDOW = 5;

    /**
     * @param list<int> $rounds bytes retained after each round, relative to the start
     */
    public function __construct(
        public array $rounds,
        public int $callsPerRound,
        public float $bytesPerCall,
        public int $lastSpread,
        public float $toleranceBytesPerCall,
        public bool $plateau,
    ) {}

    /**
     * @param list<int> $rounds
     */
    public static function fromRounds(array $rounds, int $callsPerRound, float $toleranceBytesPerCall): self
    {
        $secondHalf = array_slice($rounds, intdiv(count($rounds), 2));
        $bytesPerCall = Stats::slope($secondHalf) / $callsPerRound;
        $last = array_slice($rounds, -self::SPREAD_WINDOW);

        return new self(
            rounds: $rounds,
            callsPerRound: $callsPerRound,
            bytesPerCall: $bytesPerCall,
            lastSpread: max($last) - min($last),
            toleranceBytesPerCall: $toleranceBytesPerCall,
            plateau: $bytesPerCall <= $toleranceBytesPerCall,
        );
    }
}
