<?php

declare(strict_types=1);

use Ufo\DTO\Benchmarks\BenchmarkBootstrap;
use Ufo\DTO\Benchmarks\Fixture\BenchmarkTransformerFactory;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Transformer\Metadata\RuntimeReflectionCache;

require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);

ini_set('display_errors', '1');
ini_set('memory_limit', '-1');

gc_enable();

date_default_timezone_set('UTC');

$runtimeReflectionCache = new RuntimeReflectionCache();
$transformerFactory = new BenchmarkTransformerFactory($runtimeReflectionCache,);
if (!DTOTransformer::isInitialized()) {
    DTOTransformer::boot(
        $transformerFactory->createFacadeTransformer(),
    );
}

return new BenchmarkBootstrap(
    runtimeReflectionCache: $runtimeReflectionCache,
    transformerFactory: $transformerFactory,
);