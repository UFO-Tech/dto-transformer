<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final class MarkdownRenderer
{
    /**
     * @param array<string, string> $environment
     * @param array{command: string, note: ?string, settings: array<string, string>, notApplicable?: list<string>} $run
     * @param list<array<string, mixed>> $results
     */
    public static function render(array $environment, array $run, array $results): string
    {
        $lines = [
            sprintf('# Benchmark run: %s, %s', $environment['mode'], $environment['date']),
            '',
            sprintf('Command: `%s`', $run['command']),
        ];
        if ($run['note'] !== null && $run['note'] !== '') {
            $lines[] = '';
            $lines[] = $run['note'];
        }

        $lines = [...$lines, '', '## Environment', '', ...self::keyValueTable($environment + $run['settings']), '', '## Results'];

        $growth = array_values(array_filter($results, static fn (array $row): bool => $row['mode'] === 'growth'));
        $steps = array_values(array_filter($results, static fn (array $row): bool => $row['mode'] !== 'growth'));

        foreach (ResultColumns::groups($steps) as $rows) {
            $lines[] = '';
            $lines[] = sprintf('### %s [%s]', self::escape($rows[0]['benchmark']), $rows[0]['mode']);
            $lines[] = '';
            array_push($lines, ...self::steps($rows));
        }

        if ($growth !== []) {
            array_push($lines, '', ...self::growth($growth));
        }

        $notMeasured = [...ResultColumns::notMeasured($results), ...($run['notApplicable'] ?? [])];
        $lines = [
            ...$lines,
            '',
            '## Not measured',
            '',
            ...($notMeasured === [] ? ['Nothing: every metric and mode of this profile is covered.'] : array_map(static fn (string $note): string => '- ' . $note, $notMeasured)),
        ];

        return implode(PHP_EOL, [...$lines, '', ...self::legend($steps !== [], $growth !== [])]) . PHP_EOL;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    private static function steps(array $rows): array
    {
        return [
            'Time and CPU',
            '',
            ...self::columns(ResultColumns::timeAndCpu($rows), $rows),
            '',
            'Memory and I/O',
            '',
            ...self::columns(ResultColumns::memoryAndIo($rows), $rows),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    private static function growth(array $rows): array
    {
        return [
            '### Memory growth [growth]',
            '',
            ...self::columns(ResultColumns::growthSummary($rows), $rows),
            '',
            'Retained memory after each round (`#N` is the scenario number in the table above):',
            '',
            ...self::columns(ResultColumns::growthRounds($rows), []),
        ];
    }

    /**
     * @param list<array{0: string, 1: list<string>, 2?: bool}> $columns
     * @param list<array<string, mixed>> $rows result rows behind the table lines, for highlighting
     * @return list<string>
     */
    private static function columns(array $columns, array $rows): array
    {
        $titles = array_column($columns, 0);
        $noiseColumn = array_search(ResultColumns::NOISE, $titles, true);
        $verdictColumn = array_search('Verdict', $titles, true);
        $lines = [
            '| ' . implode(' | ', array_map(static fn (array $column): string => self::escape($column[0]), $columns)) . ' |',
            '|' . implode('|', array_map(static fn (array $column): string => ($column[2] ?? false) ? ':---' : '---:', $columns)) . '|',
        ];

        foreach (array_keys($columns[0][1]) as $index) {
            $cells = [];
            foreach ($columns as $position => $column) {
                $value = $column[1][$index];
                $cell = self::escape($value);
                $highlight = match ($position) {
                    $noiseColumn => ResultColumns::noiseLevel($rows[$index]) > 0,
                    $verdictColumn => $value !== 'plateau',
                    default => false,
                };
                $cells[] = $highlight ? '**' . $cell . '**' : $cell;
            }
            $lines[] = '| ' . implode(' | ', $cells) . ' |';
        }

        return $lines;
    }

    /**
     * @param array<string, string> $values
     * @return list<string>
     */
    private static function keyValueTable(array $values): array
    {
        $lines = ['| Key | Value |', '|---|---|'];
        foreach ($values as $key => $value) {
            $lines[] = sprintf('| %s | %s |', self::escape((string) $key), self::escape((string) $value));
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function legend(bool $steps, bool $growth): array
    {
        $lines = ['## How to read', ''];

        if ($steps) {
            array_push(
                $lines,
                '- **Median** is the headline time per call; `Per <unit>` and `<unit>/s` are derived from it.',
                sprintf(
                    '- **Noise** = IQR / median of the samples; bold above %s. Treat differences smaller than the noise as no change.',
                    Format::percent(ResultColumns::NOISY),
                ),
                '- **CPU** is user + system time per unit over all samples of the step; **CPU %** = CPU / wall time: about 100 % means compute-bound, well below means waiting.',
                '- **Alloc** is the net peak memory of one call above its baseline, including the result; cache eviction during the call lowers it.',
                '- **Read / Written** are bytes through read and write system calls (page cache included); **Disk** columns are bytes that reached the storage.',
                '- **Retained** is what stays after the result is dropped and cycles are collected; about 0 in warm mode.',
                '- The smallest steps are dominated by fixed costs; throughput claims come from steps with flat per-unit values.',
            );
        }

        if ($growth) {
            array_push(
                $lines,
                '- **Retained** after a round is the memory left in the process after the round\'s calls and garbage collection, relative to the start.',
                '- **Slope** is the growth per call over the second half of the rounds; **plateau** means it stays within the tolerance, **grows** (bold) means memory keeps rising with the number of calls.',
                '- A high but flat first round is caches filling up to their limits; only a rising tail is a leak.',
                '- **Process peak so far** is cumulative: scenarios run one after another in the same process.',
            );
        }

        $lines[] = '- Raw samples: `results.json`. Compare two runs: `php benchmarks/compare.php <run-dir-a> <run-dir-b>`.';

        return $lines;
    }

    private static function escape(string $text): string
    {
        return str_replace('|', '\\|', $text);
    }
}
