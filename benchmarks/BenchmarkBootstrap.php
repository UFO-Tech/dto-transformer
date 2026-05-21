<?php

declare(strict_types = 1);

namespace Ufo\DTO\Benchmarks;

use Ufo\DTO\Benchmarks\Fixture\BenchmarkTransformerFactory;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;

final readonly class BenchmarkBootstrap
{
    public function __construct(
        public RuntimeReflectionCacheInterface $runtimeReflectionCache,
        public BenchmarkTransformerFactory $transformerFactory,
    ) {}

    public function clearRuntimeState(): void
    {
        $this->runtimeReflectionCache->clear();
    }
}