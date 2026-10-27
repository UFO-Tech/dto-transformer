<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use Ufo\DTO\Benchmarks\DTOTransformer\DTOFromArrayTransformerBench;
use Ufo\DTO\Benchmarks\DTOTransformer\DTOToArrayTransformerBench;
use Ufo\DTO\Benchmarks\DTOTransformer\FromArrayBench;
use Ufo\DTO\Benchmarks\DTOTransformer\NestingBench;
use Ufo\DTO\Benchmarks\DTOTransformer\PressureBench;
use Ufo\DTO\Benchmarks\DTOTransformer\ToArrayBench;
use Ufo\DTO\Benchmarks\Fixture\FilesystemCache;
use Ufo\DTO\Benchmarks\Fixture\TransformerGraph;
use Ufo\DTO\Benchmarks\Growth\FixtureRoundTripGrowth;
use Ufo\DTO\Benchmarks\Growth\ManyClassesGrowth;
use Ufo\DTO\Benchmarks\Growth\UniqueDefaultNamespaceGrowth;
use Ufo\DTO\Benchmarks\Growth\UniqueNamespaceComboGrowth;

require dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');

$mode = Runner::options($argv)['mode'];
if (!isset(Runner::options($argv)['alloc-only'])) {
    gc_enable();
}
if (in_array($mode, ['cold-persistent', 'cold-persistent-empty'], true) && getenv('BENCH_CACHE_DIR') === false) {
    $cacheDir = sys_get_temp_dir() . '/dto-bench-cache-' . getmypid();
    putenv('BENCH_CACHE_DIR=' . $cacheDir);
    putenv('BENCH_CACHE_FRESH=' . ($mode === 'cold-persistent-empty' ? '1' : '0'));
    register_shutdown_function(static fn () => FilesystemCache::removeDirectory($cacheDir));
}

$cacheDir = getenv('BENCH_CACHE_DIR');
if ($cacheDir !== false && getenv('BENCH_CACHE_FRESH') === '1') {
    $cacheDir .= '/' . getmypid();
    register_shutdown_function(static fn () => FilesystemCache::removeDirectory($cacheDir));
}
$graph = new TransformerGraph($cacheDir === false ? null : new FilesystemCache($cacheDir));

exit(new Runner(
    benchmarks: [
        new FromArrayBench(),
        new ToArrayBench(),
        new DTOFromArrayTransformerBench($graph),
        new DTOToArrayTransformerBench($graph),
        new NestingBench(),
        new NestingBench(toArray: true),
        new PressureBench(),
        new PressureBench(toArray: true),
    ],
    growthScenarios: [
        new FixtureRoundTripGrowth(),
        new UniqueDefaultNamespaceGrowth(),
        new UniqueNamespaceComboGrowth(),
        new ManyClassesGrowth(),
    ],
    reset: $graph->rebuild(...),
    partialReset: true,
    resultsDirectory: __DIR__ . '/results',
    notApplicable: [
        'concurrency: not applicable, PHP serves one request per process and the library shares no state between processes.',
    ],
)->run($argv));
