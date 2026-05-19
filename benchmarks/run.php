<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks;

require_once __DIR__ . '/bootstrap.php';

use Ufo\DTO\Benchmarks\DTOTransformer\FromArrayBench;
use Ufo\DTO\Benchmarks\DTOTransformer\ToArrayBench;

$runner = new BenchmarkRunner([
    new FromArrayBench(),
    new ToArrayBench(),
]);

$runner->run();