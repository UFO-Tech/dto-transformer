<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use Closure;
use RuntimeException;

final readonly class Runner
{
    public const array MODES = ['warm', 'reset', 'cold-process', 'cold-persistent', 'cold-persistent-empty', 'growth', 'first-call'];

    private const array DEFAULTS = [
        'mode' => 'warm',
        'format' => 'table',
        'samples' => '15',
        'memory-samples' => '5',
        'target-ms' => '1',
        'processes' => '15',
        'rounds' => '20',
        'per-round' => '5000',
        'tolerance' => '1',
    ];

    /**
     * @param list<BenchmarkInterface> $benchmarks
     * @param list<GrowthScenarioInterface> $growthScenarios
     * @param list<string> $notApplicable modes or metrics the profile mentions but this project cannot have, with reasons
     */
    public function __construct(
        private array $benchmarks,
        private array $growthScenarios = [],
        private ?Closure $reset = null,
        private bool $partialReset = false,
        private ?string $resultsDirectory = null,
        private array $notApplicable = [],
    ) {}

    /**
     * @param list<string> $argv
     * @return array<string, string>
     */
    public static function options(array $argv): array
    {
        $options = self::DEFAULTS;

        foreach (array_slice($argv, 1) as $argument) {
            if (!str_starts_with($argument, '--')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', substr($argument, 2), 2), 2, '1');
            $options[$key] = $value;
        }

        return $options;
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $options = self::options($argv);
        $mode = $options['mode'];

        $error = match (true) {
            !in_array($mode, self::MODES, true) => sprintf('Unknown mode "%s"; expected one of: %s', $mode, implode(', ', self::MODES)),
            Environment::xdebugActive() && !isset($options['allow-xdebug']) => 'Xdebug is active; run with XDEBUG_MODE=off or pass --allow-xdebug',
            $mode === 'reset' && $this->reset === null => 'Mode "reset" needs a reset hook passed to the Runner',
            default => null,
        };
        if ($error !== null) {
            fwrite(STDERR, $error . PHP_EOL);

            return 2;
        }

        $label = $mode === 'reset' && $this->partialReset ? 'partial-reset' : $mode;
        $environment = Environment::capture($label, (string) getcwd());
        $results = match ($mode) {
            'growth' => $this->runGrowth($options),
            'first-call' => $this->runFirstCall($options),
            'cold-process', 'cold-persistent', 'cold-persistent-empty' => $this->runColdProcess($argv[0], $options, $mode),
            default => $this->runSteps($argv[0], $options, $label, new Measurer($mode === 'reset' ? $this->reset : null)),
        };

        if ($this->resultsDirectory !== null && $mode !== 'first-call' && !isset($options['no-save'])) {
            $saved = new ResultStore($this->resultsDirectory)->save($environment, $this->runInfo($argv, $options, $mode), $results);
            fwrite(STDERR, 'Saved: ' . str_replace((string) getcwd() . '/', '', $saved) . PHP_EOL);
        }

        if ($options['format'] === 'json') {
            echo json_encode(
                ['environment' => $environment, 'results' => $results],
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES,
            ), PHP_EOL;

            return 0;
        }

        new TableRenderer(stream_isatty(STDOUT))->render($environment, $results, $this->notApplicable);

        return 0;
    }

    /**
     * @param list<string> $argv
     * @param array<string, string> $options
     * @return array{command: string, note: ?string, settings: array<string, string>, notApplicable: list<string>}
     */
    private function runInfo(array $argv, array $options, string $mode): array
    {
        $keys = match ($mode) {
            'growth' => ['rounds', 'per-round', 'tolerance'],
            'cold-process', 'cold-persistent', 'cold-persistent-empty' => ['processes'],
            default => ['samples', 'memory-samples', 'target-ms'],
        };

        $settings = [];
        foreach ([...$keys, 'filter', 'exact', 'step'] as $key) {
            if (isset($options[$key])) {
                $settings[$key] = $options[$key];
            }
        }

        return [
            'command' => 'php ' . implode(' ', array_map(
                static fn (string $argument): string => preg_match('/\s/', $argument) === 1 ? escapeshellarg($argument) : $argument,
                $argv,
            )),
            'note' => $options['note'] ?? null,
            'settings' => $settings,
            'notApplicable' => $this->notApplicable,
        ];
    }

    /**
     * @param array<string, string> $options
     * @return list<array<string, mixed>>
     */
    private function runSteps(string $script, array $options, string $label, Measurer $measurer): array
    {
        $results = [];

        foreach ($this->selectedSteps($options) as [$benchmark, $step]) {
            $this->progress($benchmark->name(), $step);
            $run = $benchmark->prepare($step);
            $benchmark->verify($step, $run());

            if (isset($options['alloc-only'])) {
                $memory = $measurer->memory($run, (int) $options['memory-samples']);
                $results[] = [
                    'benchmark' => $benchmark->name(),
                    'step' => $step,
                    'memory' => ['allocated' => $memory->allocatedMedian, 'allocatedSamples' => $memory->allocated],
                ];

                continue;
            }

            $calls = $measurer->calibrate($run, (float) $options['target-ms']);
            [$time, $resources] = $measurer->time($run, (int) $options['samples'], $calls);
            $memory = $measurer->memory($run, (int) $options['memory-samples']);
            $allocated = $this->allocInChild($script, $options['mode'], $benchmark->name(), $step, $options);

            $results[] = [
                'benchmark' => $benchmark->name(),
                'mode' => $label,
                'step' => $step,
                'units' => $benchmark->units($step),
                'unit' => $benchmark->unitName(),
                'time' => [
                    'median' => $time->median,
                    'min' => $time->min,
                    'max' => $time->max,
                    'noise' => $time->noise,
                    'callsPerSample' => $time->callsPerSample,
                    'samples' => $time->samples,
                ],
                'cpu' => $resources->cpuRow(),
                'memory' => [
                    'allocated' => $allocated['allocated'],
                    'retained' => $memory->retainedMedian,
                    'allocatedSamples' => $allocated['allocatedSamples'],
                    'retainedSamples' => $memory->retained,
                ],
                'io' => $resources->io,
            ];
        }

        return $results;
    }

    /**
     * @param array<string, string> $options
     * @return list<array<string, mixed>>
     */
    private function runFirstCall(array $options): array
    {
        $measurer = new Measurer($this->reset);
        $results = [];

        foreach ($this->selectedSteps($options) as [$benchmark, $step]) {
            $run = $benchmark->prepare($step);
            [$milliseconds, $allocated, $retained, $resources] = $measurer->firstCall(
                $run,
                static fn (mixed $result) => $benchmark->verify($step, $result),
            );

            $results[] = [
                'benchmark' => $benchmark->name(),
                'mode' => 'first-call',
                'step' => $step,
                'units' => $benchmark->units($step),
                'unit' => $benchmark->unitName(),
                'time' => ['median' => $milliseconds],
                'cpu' => $resources->cpuRow(),
                'memory' => ['allocated' => $allocated, 'retained' => $retained],
                'io' => $resources->io,
            ];
        }

        return $results;
    }

    /**
     * @param array<string, string> $options
     * @return list<array<string, mixed>>
     */
    private function runColdProcess(string $script, array $options, string $mode): array
    {
        $results = [];

        foreach ($this->selectedSteps($options) as [$benchmark, $step]) {
            $this->progress($benchmark->name(), $step);
            if ($mode === 'cold-persistent') {
                $this->firstCallInChild($script, $benchmark->name(), $step, $options);
            }

            $times = [];
            $allocated = [];
            $retained = [];
            $cpu = [];
            $io = [];
            for ($process = 0; $process < (int) $options['processes']; $process++) {
                $row = $this->firstCallInChild($script, $benchmark->name(), $step, $options);
                $times[] = (float) $row['time']['median'];
                $allocated[] = (int) $row['memory']['allocated'];
                $retained[] = (int) $row['memory']['retained'];
                $cpu[] = $row['cpu'];
                $io[] = $row['io'];
            }

            $time = TimeStats::fromSamples($times, 1);
            $results[] = [
                'benchmark' => $benchmark->name(),
                'mode' => $mode,
                'step' => $step,
                'units' => $benchmark->units($step),
                'unit' => $benchmark->unitName(),
                'time' => [
                    'median' => $time->median,
                    'min' => $time->min,
                    'max' => $time->max,
                    'noise' => $time->noise,
                    'callsPerSample' => 1,
                    'samples' => $times,
                ],
                'cpu' => ResourceStats::medianOf($cpu),
                'memory' => [
                    'allocated' => Stats::median($allocated),
                    'retained' => Stats::median($retained),
                    'allocatedSamples' => $allocated,
                    'retainedSamples' => $retained,
                ],
                'io' => in_array(null, $io, true) ? null : ResourceStats::medianOf($io),
            ];
        }

        return $results;
    }

    /**
     * @param array<string, string> $options
     * @return list<array<string, mixed>>
     */
    private function runGrowth(array $options): array
    {
        $measurer = new Measurer();
        $results = [];

        foreach ($this->growthScenarios as $scenario) {
            if (!$this->matches($scenario->name(), $options)) {
                continue;
            }

            $this->progress($scenario->name(), null);
            $growth = $measurer->growth(
                $scenario->prepare(),
                (int) $options['rounds'],
                (int) $options['per-round'],
                (float) $options['tolerance'],
            );

            $results[] = [
                'benchmark' => $scenario->name(),
                'mode' => 'growth',
                'rounds' => $growth->rounds,
                'callsPerRound' => $growth->callsPerRound,
                'bytesPerCall' => $growth->bytesPerCall,
                'lastSpread' => $growth->lastSpread,
                'toleranceBytesPerCall' => $growth->toleranceBytesPerCall,
                'plateau' => $growth->plateau,
                'peak' => memory_get_peak_usage(),
            ];
        }

        return $results;
    }

    /**
     * @param array<string, string> $options
     * @return array<string, mixed>
     */
    private function firstCallInChild(string $script, string $name, int $step, array $options): array
    {
        $command = [PHP_BINARY, $script, '--mode=first-call', '--format=json', '--exact=' . $name, '--step=' . $step];

        return $this->childRow($command, $name, $step, $options);
    }

    /**
     * Alloc одного виклику в окремому процесі з вимкненим GC.
     *
     * @param array<string, string> $options
     * @return array{allocated: float, allocatedSamples: list<int>}
     */
    private function allocInChild(string $script, string $mode, string $name, int $step, array $options): array
    {
        $command = [
            PHP_BINARY, '-d', 'zend.enable_gc=0', $script, '--mode=' . $mode, '--alloc-only', '--no-save', '--format=json',
            '--exact=' . $name, '--step=' . $step, '--memory-samples=' . $options['memory-samples'],
        ];

        return $this->childRow($command, $name, $step, $options)['memory'];
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $options
     * @return array<string, mixed>
     */
    private function childRow(array $command, string $name, int $step, array $options): array
    {
        if (isset($options['allow-xdebug'])) {
            $command[] = '--allow-xdebug';
        }

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start a child PHP process');
        }

        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new RuntimeException(sprintf('Child process failed for "%s" step %d: %s', $name, $step, trim($errors)));
        }

        $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        if (!isset($decoded['results'][0])) {
            throw new RuntimeException(sprintf('Child process returned no result for "%s" step %d', $name, $step));
        }

        return $decoded['results'][0];
    }

    /**
     * @param array<string, string> $options
     * @return iterable<array{BenchmarkInterface, int}>
     */
    private function selectedSteps(array $options): iterable
    {
        foreach ($this->benchmarks as $benchmark) {
            if (!$this->matches($benchmark->name(), $options)) {
                continue;
            }

            foreach ($benchmark->steps() as $step) {
                if (isset($options['step']) && (int) $options['step'] !== $step) {
                    continue;
                }

                yield [$benchmark, $step];
            }
        }
    }

    /**
     * @param array<string, string> $options
     */
    private function matches(string $name, array $options): bool
    {
        if (isset($options['exact'])) {
            return $name === $options['exact'];
        }

        return !isset($options['filter']) || str_contains($name, $options['filter']);
    }

    private function progress(string $name, ?int $step): void
    {
        fwrite(STDERR, sprintf("  %s%s\n", $name, $step === null ? '' : ' · step ' . $step));
    }
}
