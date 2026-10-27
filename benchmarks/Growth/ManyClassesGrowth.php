<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Growth;

use Closure;
use Ufo\DTO\Benchmarks\Fixture\GeneratedClasses;
use Ufo\DTO\Benchmarks\GrowthScenarioInterface;
use Ufo\DTO\DTOTransformer;

final class ManyClassesGrowth implements GrowthScenarioInterface
{
    private const int CLASSES = 3000;

    public function name(): string
    {
        return 'growth: 3000 classes in a cycle, unique $defaultNamespace';
    }

    public function prepare(): Closure
    {
        $classes = GeneratedClasses::ensure(self::CLASSES);
        $payloads = array_map(GeneratedClasses::payload(...), array_keys($classes));

        return static fn (int $i): object => DTOTransformer::fromArray(
            $classes[$i % self::CLASSES],
            $payloads[$i % self::CLASSES],
            namespaces: ['$defaultNamespace' => 'Ns' . $i],
        );
    }
}
