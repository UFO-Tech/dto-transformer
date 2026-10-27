<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Growth;

use Closure;
use Ufo\DTO\Benchmarks\Fixture\DocBlockItemHolder;
use Ufo\DTO\Benchmarks\Fixture\GeneratedClasses;
use Ufo\DTO\Benchmarks\GrowthScenarioInterface;
use Ufo\DTO\DTOTransformer;

/**
 * Тип з докблоку розв'язується через namespaces в один з існуючих класів; комбінація щоразу нова.
 */
final class UniqueNamespaceComboGrowth implements GrowthScenarioInterface
{
    private const int CLASSES = 1000;

    public function name(): string
    {
        return 'growth: docblock type -> existing class, unique namespaces combo';
    }

    public function prepare(): Closure
    {
        $classes = GeneratedClasses::ensure(self::CLASSES);
        $payloads = array_map(GeneratedClasses::payload(...), array_keys($classes));

        return static fn (int $i): object => DTOTransformer::fromArray(
            DocBlockItemHolder::class,
            ['item' => $payloads[$i % self::CLASSES]],
            namespaces: ['Item' => $classes[$i % self::CLASSES], '$defaultNamespace' => 'Ns' . $i],
        );
    }
}
