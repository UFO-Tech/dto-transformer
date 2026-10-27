<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use InvalidArgumentException;

final class Stats
{
    /**
     * @param list<int|float> $values
     */
    public static function median(array $values): float
    {
        return self::percentile($values, 50.0);
    }

    /**
     * @param list<int|float> $values
     */
    public static function percentile(array $values, float $percent): float
    {
        if ($values === []) {
            throw new InvalidArgumentException('Cannot compute a percentile of an empty sample');
        }

        sort($values);
        $rank = $percent / 100 * (count($values) - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);

        return $values[$low] + ($values[$high] - $values[$low]) * ($rank - $low);
    }

    /**
     * @param list<int|float> $values
     */
    public static function slope(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $meanX = ($count - 1) / 2;
        $meanY = array_sum($values) / $count;
        $numerator = 0.0;
        $denominator = 0.0;

        foreach (array_values($values) as $x => $y) {
            $numerator += ($x - $meanX) * ($y - $meanY);
            $denominator += ($x - $meanX) ** 2;
        }

        return $numerator / $denominator;
    }
}
