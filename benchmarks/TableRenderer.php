<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

final readonly class TableRenderer
{
    private const int LINE_WIDTH = 100;

    private const string GRAY = '90';
    private const string CYAN = '36';
    private const string GREEN = '32';
    private const string YELLOW = '33';
    private const string RED = '31';

    public function __construct(
        private bool $colors,
    ) {}

    /**
     * @param array<string, string> $environment
     * @param list<array<string, mixed>> $results
     * @param list<string> $notApplicable
     */
    public function render(array $environment, array $results, array $notApplicable = []): void
    {
        $this->environment($environment);

        $growth = array_values(array_filter($results, static fn (array $row): bool => $row['mode'] === 'growth'));
        $steps = array_values(array_filter($results, static fn (array $row): bool => $row['mode'] !== 'growth'));

        foreach (ResultColumns::groups($steps) as $rows) {
            echo PHP_EOL, $this->paint(sprintf('%s [%s]', $rows[0]['benchmark'], $rows[0]['mode']), self::CYAN), PHP_EOL;
            $this->table($rows);
        }

        if ($growth !== []) {
            $this->growth($growth);
        }

        foreach ([...ResultColumns::notMeasured($results), ...$notApplicable] as $note) {
            echo $this->paint('Not measured: ' . $note, self::YELLOW), PHP_EOL;
        }
    }

    /**
     * @param array<string, string> $environment
     */
    private function environment(array $environment): void
    {
        $line = '';
        foreach ($environment as $key => $value) {
            $pair = $key . '=' . $value;
            if ($line !== '' && Format::width($line . '  ' . $pair) > self::LINE_WIDTH) {
                echo $this->paint($line, self::GRAY), PHP_EOL;
                $line = '';
            }
            $line = $line === '' ? $pair : $line . '  ' . $pair;
        }

        echo $this->paint($line, self::GRAY), PHP_EOL;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function table(array $rows): void
    {
        echo $this->paint('Time and CPU', self::GRAY), PHP_EOL;
        $this->columns(ResultColumns::timeAndCpu($rows), $rows);
        echo $this->paint('Memory and I/O', self::GRAY), PHP_EOL;
        $this->columns(ResultColumns::memoryAndIo($rows), $rows);
    }

    /**
     * @param list<array{0: string, 1: list<string>, 2?: bool}> $columns
     * @param list<array<string, mixed>> $rows result rows behind the table lines, for highlighting
     */
    private function columns(array $columns, array $rows): void
    {
        $widths = array_map(
            static fn (array $column): int => max(Format::width($column[0]), ...array_map(Format::width(...), $column[1])),
            $columns,
        );
        $pad = static fn (string $text, int $width, bool $left): string => $left
            ? $text . str_repeat(' ', max(0, $width - Format::width($text)))
            : Format::padLeft($text, $width);

        $header = implode('  ', array_map(
            static fn (array $column, int $width): string => $pad($column[0], $width, $column[2] ?? false),
            $columns,
            $widths,
        ));
        echo $this->paint($header, self::GRAY), PHP_EOL;
        echo $this->paint(str_repeat('─', Format::width($header)), self::GRAY), PHP_EOL;

        $titles = array_column($columns, 0);
        $noiseColumn = array_search(ResultColumns::NOISE, $titles, true);
        $verdictColumn = array_search('Verdict', $titles, true);
        foreach (array_keys($columns[0][1]) as $index) {
            $cells = [];
            foreach ($columns as $position => $column) {
                $value = $column[1][$index];
                $cell = $pad($value, $widths[$position], $column[2] ?? false);
                $cells[] = match ($position) {
                    $noiseColumn => $this->paint($cell, [0 => '', 1 => self::YELLOW, 2 => self::RED][ResultColumns::noiseLevel($rows[$index])]),
                    $verdictColumn => $this->paint($cell, $value === 'plateau' ? self::GREEN : self::RED),
                    default => $cell,
                };
            }
            echo implode('  ', $cells), PHP_EOL;
        }
        echo PHP_EOL;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function growth(array $rows): void
    {
        echo PHP_EOL, $this->paint('Memory growth [growth]', self::CYAN), PHP_EOL;
        $this->columns(ResultColumns::growthSummary($rows), $rows);
        echo $this->paint('Retained memory after each round (#N = scenario in the table above)', self::GRAY), PHP_EOL;
        $this->columns(ResultColumns::growthRounds($rows), []);
    }

    private function paint(string $text, string $color): string
    {
        return $this->colors && $color !== '' ? "\033[" . $color . 'm' . $text . "\033[0m" : $text;
    }
}
