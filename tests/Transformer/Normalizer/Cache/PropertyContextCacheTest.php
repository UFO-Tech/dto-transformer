<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Normalizer\Cache;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Normalizer\Cache\PropertyContextCache;

final class PropertyContextCacheTest extends TestCase
{
    public function testReturnsStoredContextsForSameParentAndClass(): void
    {
        $cache = new PropertyContextCache();
        $parent = NormalizationContext::create();
        $contexts = ['name' => $parent->nextDepth()];

        $this->assertNull($cache->get($parent, self::class));
        $this->assertSame($contexts, $cache->set($parent, self::class, $contexts));
        $this->assertSame($contexts, $cache->get($parent, self::class));
    }

    public function testKeepsEntriesSeparatePerParentAndClass(): void
    {
        $cache = new PropertyContextCache();
        $parent = NormalizationContext::create();
        $cache->set($parent, self::class, ['name' => $parent->nextDepth()]);

        $this->assertNull($cache->get(NormalizationContext::create(), self::class));
        $this->assertNull($cache->get($parent, TestCase::class));
    }

    public function testDropsEntriesWithParentContext(): void
    {
        $cache = new PropertyContextCache();
        $parent = NormalizationContext::create();
        $cache->set($parent, self::class, ['name' => $parent->nextDepth()]);
        unset($parent);

        $contexts = (new ReflectionProperty($cache, 'contexts'))->getValue($cache);
        $this->assertCount(0, $contexts);
    }
}
