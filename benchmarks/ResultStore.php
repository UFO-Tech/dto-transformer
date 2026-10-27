<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use RuntimeException;

/**
 * Тека на кожен запуск: report.md і results.json; коренева тека не трекається git завдяки власному .gitignore.
 */
final readonly class ResultStore
{
    public function __construct(
        private string $directory,
    ) {}

    /**
     * @param array<string, string> $environment
     * @param array{command: string, note: ?string, settings: array<string, string>, notApplicable?: list<string>} $run
     * @param list<array<string, mixed>> $results
     */
    public function save(array $environment, array $run, array $results): string
    {
        $this->ensureIgnored();
        $target = $this->uniqueDirectory($this->runName($environment));

        if (!mkdir($target, 0777, true) && !is_dir($target)) {
            throw new RuntimeException(sprintf('Cannot create results directory "%s"', $target));
        }

        file_put_contents(
            $target . '/results.json',
            json_encode(
                ['environment' => $environment, 'run' => $run, 'results' => $results],
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ) . PHP_EOL,
        );
        file_put_contents($target . '/report.md', MarkdownRenderer::render($environment, $run, $results));

        return $target;
    }

    private function ensureIgnored(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cannot create results directory "%s"', $this->directory));
        }

        $gitignore = $this->directory . '/.gitignore';
        if (!is_file($gitignore)) {
            file_put_contents($gitignore, '*' . PHP_EOL);
        }
    }

    /**
     * @param array<string, string> $environment
     */
    private function runName(array $environment): string
    {
        $xdebug = in_array($environment['xdebug'], ['not loaded', 'off'], true) ? 'off' : 'on';
        $name = sprintf(
            '%s_%s_php%s_opcache-%s_jit-%s_xdebug-%s_%s%s',
            gmdate('Y-m-d_H-i-s', (int) strtotime($environment['date'])),
            $environment['mode'],
            $environment['php'],
            $environment['opcache'],
            $environment['jit'],
            $xdebug,
            $environment['commit'],
            $environment['dirty'] === 'yes' ? '-dirty' : '',
        );

        return (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
    }

    private function uniqueDirectory(string $name): string
    {
        $path = $this->directory . '/' . $name;
        for ($suffix = 2; is_dir($path); $suffix++) {
            $path = $this->directory . '/' . $name . '_' . $suffix;
        }

        return $path;
    }
}
