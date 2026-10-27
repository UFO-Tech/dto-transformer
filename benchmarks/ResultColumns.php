<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final class ResultColumns
{
    public const string NOISE = 'Noise';

    public const float NOISY = 0.10;
    public const float VERY_NOISY = 0.25;

    /**
     * @param list<array<string, mixed>> $rows step rows of one benchmark and mode
     * @return list<array{string, list<string>}> column title and its cells
     */
    public static function timeAndCpu(array $rows): array
    {
        $unit = self::unit($rows);
        $perUnit = array_map(static fn (array $row): float => $row['time']['median'] / self::units($row), $rows);
        $time = static fn (string $key): array => array_map(
            static fn (array $row): string => isset($row['time'][$key]) ? Format::duration($row['time'][$key]) : '-',
            $rows,
        );

        return [
            ...self::identity($rows),
            ['Median', $time('median')],
            ['Per ' . $unit, array_map(Format::duration(...), $perUnit)],
            [$unit . '/s', array_map(static fn (float $value): string => $value > 0.0 ? Format::number(1000 / $value, 0) : '-', $perUnit)],
            ['Min', $time('min')],
            ['Max', $time('max')],
            [self::NOISE, array_map(
                static fn (array $row): string => isset($row['time']['noise']) ? Format::percent($row['time']['noise']) : '-',
                $rows,
            )],
            ['CPU/' . $unit, array_map(
                static fn (array $row): string => isset($row['cpu']) ? Format::duration($row['cpu']['perCall'] / self::units($row)) : '-',
                $rows,
            )],
            ['CPU %', array_map(
                static fn (array $row): string => isset($row['cpu']) ? Format::percent($row['cpu']['percent']) : '-',
                $rows,
            )],
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{string, list<string>}>
     */
    public static function memoryAndIo(array $rows): array
    {
        $unit = self::unit($rows);
        $io = static fn (string $key): array => array_map(
            static fn (array $row): string => isset($row['io']) ? Format::bytes($row['io'][$key] / self::units($row)) : '-',
            $rows,
        );

        return [
            ...self::identity($rows),
            ['Alloc', array_map(static fn (array $row): string => Format::bytes($row['memory']['allocated']), $rows)],
            ['Alloc/' . $unit, array_map(
                static fn (array $row): string => Format::bytes($row['memory']['allocated'] / self::units($row)),
                $rows,
            )],
            ['Retained', array_map(static fn (array $row): string => Format::bytes($row['memory']['retained']), $rows)],
            ['Read/' . $unit, $io('readPerCall')],
            ['Written/' . $unit, $io('writtenPerCall')],
            ['Disk read/' . $unit, $io('storageReadPerCall')],
            ['Disk written/' . $unit, $io('storageWrittenPerCall')],
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows growth rows of one run
     * @return list<array{0: string, 1: list<string>, 2?: bool}> the third element marks a left-aligned column
     */
    public static function growthSummary(array $rows): array
    {
        $bytes = static fn (callable $value): array => array_map(static fn (array $row): string => Format::bytes($value($row)), $rows);

        return [
            ['#', array_map(static fn (int $index): string => (string) ($index + 1), array_keys($rows))],
            ['Scenario', array_map(static fn (array $row): string => $row['benchmark'], $rows), true],
            ['Verdict', array_map(static fn (array $row): string => $row['plateau'] ? 'plateau' : 'grows', $rows), true],
            ['Slope', array_map(static fn (array $row): string => Format::number($row['bytesPerCall'], 2) . ' B/call', $rows)],
            ['Tolerance', array_map(static fn (array $row): string => Format::number($row['toleranceBytesPerCall'], 2) . ' B/call', $rows)],
            ['First round', $bytes(static fn (array $row): int => $row['rounds'][0])],
            ['Last round', $bytes(static fn (array $row): int => $row['rounds'][count($row['rounds']) - 1])],
            ['Spread of last rounds', $bytes(static fn (array $row): int => $row['lastSpread'])],
            ['Calls/round', array_map(static fn (array $row): string => Format::number($row['callsPerRound'], 0), $rows)],
            ['Process peak so far', $bytes(static fn (array $row): int => $row['peak'])],
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows growth rows of one run
     * @return list<array{0: string, 1: list<string>, 2?: bool}> one row per round, one column per scenario
     */
    public static function growthRounds(array $rows): array
    {
        $rounds = max(array_map(static fn (array $row): int => count($row['rounds']), $rows));
        $indexes = range(0, $rounds - 1);

        $columns = [
            ['Round', array_map(static fn (int $index): string => (string) ($index + 1), $indexes)],
            ['Calls', array_map(static fn (int $index): string => Format::number(($index + 1) * $rows[0]['callsPerRound'], 0), $indexes)],
        ];
        foreach ($rows as $position => $row) {
            $columns[] = [
                'Retained #' . ($position + 1),
                array_map(static fn (int $index): string => isset($row['rounds'][$index]) ? Format::bytes($row['rounds'][$index]) : '-', $indexes),
            ];
        }

        return $columns;
    }

    /**
     * @param list<array<string, mixed>> $results
     * @return list<string>
     */
    public static function notMeasured(array $results): array
    {
        $steps = array_filter($results, static fn (array $row): bool => $row['mode'] !== 'growth');
        $notes = [];

        if (array_filter($steps, static fn (array $row): bool => !isset($row['cpu'])) !== []) {
            $notes[] = 'CPU: getrusage() data missing for some rows.';
        }
        if (array_filter($steps, static fn (array $row): bool => !isset($row['io'])) !== []) {
            $notes[] = 'I/O: /proc/self/io is not readable on this platform (Linux only).';
        }

        return $notes;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function noiseLevel(array $row): int
    {
        $noise = $row['time']['noise'] ?? null;

        return match (true) {
            $noise === null, $noise < self::NOISY => 0,
            $noise < self::VERY_NOISY => 1,
            default => 2,
        };
    }

    /**
     * @param list<array<string, mixed>> $results
     * @return list<list<array<string, mixed>>>
     */
    public static function groups(array $results): array
    {
        $groups = [];
        $current = null;

        foreach ($results as $row) {
            $key = $row['benchmark'] . '|' . $row['mode'];
            if ($key !== $current) {
                $groups[] = [];
                $current = $key;
            }
            $groups[count($groups) - 1][] = $row;
        }

        return $groups;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{string, list<string>}>
     */
    private static function identity(array $rows): array
    {
        $columns = [['Step', array_map(static fn (array $row): string => (string) $row['step'], $rows)]];
        if (array_filter($rows, static fn (array $row): bool => $row['units'] !== $row['step']) !== []) {
            $columns[] = ['Units', array_map(static fn (array $row): string => (string) $row['units'], $rows)];
        }

        return $columns;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private static function unit(array $rows): string
    {
        return $rows[0]['unit'] ?? 'unit';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function units(array $row): int
    {
        return max(1, (int) $row['units']);
    }
}
