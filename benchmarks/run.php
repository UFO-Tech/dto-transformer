<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

use Ufo\DTO\Benchmarks\DTOTransformer\DTOFromArrayTransformerBench;
use Ufo\DTO\Benchmarks\DTOTransformer\DTOToArrayTransformerBench;
use Ufo\DTO\Benchmarks\DTOTransformer\FromArrayBench;
use Ufo\DTO\Benchmarks\DTOTransformer\ToArrayBench;

/** @var BenchmarkBootstrap $bootstrap */
$bootstrap = require __DIR__ . '/bootstrap.php';

$fromArrayTransformer = $bootstrap
    ->transformerFactory
    ->createFromArrayTransformer();

$toArrayTransformer = $bootstrap
    ->transformerFactory
    ->createToArrayTransformer();

$runner = new BenchmarkRunner([
    new FromArrayBench(
        $bootstrap->clearRuntimeState(...),
    ),

    new ToArrayBench(
        $bootstrap->clearRuntimeState(...),
    ),

    new DTOFromArrayTransformerBench(
        $fromArrayTransformer,
        $bootstrap->clearRuntimeState(...),
    ),

    new DTOToArrayTransformerBench(
        $toArrayTransformer,
        $bootstrap->clearRuntimeState(...),
    ),
]);

$runner->run();