<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer\Cache;

use Ufo\DTO\Transformer\Context\NormalizationContext;
use WeakMap;

/**
 * Готові контексти властивостей класу для батьківського контексту; живуть, доки живе батьківський контекст.
 */
class PropertyContextCache
{
    /**
     * @var WeakMap<NormalizationContext, array<class-string, array<string, NormalizationContext>>>
     */
    protected WeakMap $contexts;

    public function __construct()
    {
        $this->contexts = new WeakMap();
    }

    /**
     * @param class-string $className
     * @return array<string, NormalizationContext>|null за іменем властивості
     */
    public function get(NormalizationContext $parent, string $className): ?array
    {
        return $this->contexts[$parent][$className] ?? null;
    }

    /**
     * @param class-string $className
     * @param array<string, NormalizationContext> $contexts за іменем властивості
     * @return array<string, NormalizationContext>
     */
    public function set(NormalizationContext $parent, string $className, array $contexts): array
    {
        $entries = $this->contexts[$parent] ?? [];
        unset($this->contexts[$parent]);
        $entries[$className] = $contexts;
        $this->contexts[$parent] = $entries;

        return $contexts;
    }
}
