<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Metadata;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Ufo\DTO\Transformer\Metadata\PhpDocumentorDocBlockParser;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataProvider;
use Ufo\DTO\Transformer\Metadata\RuntimeReflectionCache;
use Ufo\DTO\Transformer\Type\ReflectionTypeSchemaResolver;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;

class RuntimeReflectionCacheTest extends TestCase
{
    public function testItRemembersRuntimeValuesWithoutSerialization(): void
    {
        $cache = new RuntimeReflectionCache();
        $object = new \stdClass();
        $calls = 0;

        $first = $cache->remember('object', function () use ($object, &$calls): object {
            $calls++;

            return $object;
        });
        $second = $cache->remember('object', function () use (&$calls): object {
            $calls++;

            return new \stdClass();
        });

        $this->assertSame($object, $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function testItEvictsLeastRecentlyUsedItems(): void
    {
        $cache = new RuntimeReflectionCache(limit: 2);

        $cache->set('first', 1);
        $cache->set('second', 2);
        $this->assertSame(1, $cache->get('first'));

        $cache->set('third', 3);

        $this->assertTrue($cache->has('first'));
        $this->assertFalse($cache->has('second'));
        $this->assertTrue($cache->has('third'));
    }

    public function testItFallsBackToPersistentCacheOnRuntimeMiss(): void
    {
        $persistentCacheKey = md5('metadata');
        $persistentCache = new InMemoryPersistentCache([$persistentCacheKey => 'from-persistent']);
        $cache = new RuntimeReflectionCache(persistentCache: $persistentCache);
        $calls = 0;

        $value = $cache->rememberPersistent('metadata', function () use (&$calls): string {
            $calls++;

            return 'from-factory';
        });

        $this->assertSame('from-persistent', $value);
        $this->assertSame(0, $calls);
        $this->assertSame(1, $persistentCache->getCalls);
    }

    public function testItHydratesRuntimeCacheFromPersistentCache(): void
    {
        $persistentCacheKey = md5('metadata');
        $persistentCache = new InMemoryPersistentCache([$persistentCacheKey => 'from-persistent']);
        $cache = new RuntimeReflectionCache(persistentCache: $persistentCache);

        $first = $cache->rememberPersistent('metadata', static fn (): string => 'from-factory');
        $second = $cache->rememberPersistent('metadata', static fn (): string => 'from-factory');

        $this->assertSame('from-persistent', $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $persistentCache->getCalls);
    }

    public function testItUsesFactoryWhenPersistentCacheMisses(): void
    {
        $persistentCache = new InMemoryPersistentCache();
        $cache = new RuntimeReflectionCache(persistentCache: $persistentCache);
        $calls = 0;

        $persistentCacheKey = md5('metadata');
        $value = $cache->rememberPersistent('metadata', function () use (&$calls): string {
            $calls++;

            return 'from-factory';
        });

        $this->assertSame('from-factory', $value);
        $this->assertSame(1, $calls);
        $this->assertSame('from-factory', $persistentCache->values[$persistentCacheKey]);
    }

    public function testItClearsRuntimeCacheOnly(): void
    {
        $persistentCache = new InMemoryPersistentCache(['metadata' => 'from-persistent']);
        $cache = new RuntimeReflectionCache(persistentCache: $persistentCache);

        $cache->rememberPersistent('metadata', static fn (): string => 'from-factory');
        $cache->clear();
        $cache->rememberPersistent('metadata', static fn (): string => 'from-factory');

        $this->assertSame(2, $persistentCache->getCalls);
    }

    public function testItDeletesRuntimeAndPersistentCacheEntries(): void
    {
        $persistentCache = new InMemoryPersistentCache(['metadata' => 'from-persistent']);
        $cache = new RuntimeReflectionCache(persistentCache: $persistentCache);

        $cache->rememberPersistent('metadata', static fn (): string => 'from-factory');
        $cache->delete('metadata');

        $persistentCacheKey = md5('metadata');
        $this->assertFalse($cache->has('metadata'));
        $this->assertArrayNotHasKey($persistentCacheKey, $persistentCache->values);
        $this->assertSame([$persistentCacheKey], $persistentCache->deletedKeys);
    }

    public function testMetadataResolversDoNotDeclareLocalMetadataArrays(): void
    {
        $typeResolverProperties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(ReflectionTypeSchemaResolver::class))->getProperties(),
        );
        $metadataProviderProperties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(ReflectionMetadataProvider::class))->getProperties(),
        );
        $docBlockParserProperties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(PhpDocumentorDocBlockParser::class))->getProperties(),
        );

        $this->assertNotContains('schemas', $typeResolverProperties);
        $this->assertNotContains('docTypeSchemas', $typeResolverProperties);
        $this->assertNotContains('nativeTypeSchemas', $typeResolverProperties);
        $this->assertNotContains('parsed', $docBlockParserProperties);
        $this->assertNotContains('items', $typeResolverProperties);
        $this->assertNotContains('items', $metadataProviderProperties);
    }

    public function testTypeSchemaMetadataStaysConsistentThroughPersistentCache(): void
    {
        $persistentCache = new InMemoryPersistentCache();
        $property = new ReflectionProperty(DummyDTO::class, 'id');

        $firstResolver = TransformerFactory::typeSchemaResolver($persistentCache);
        $first = $firstResolver->schema($property);

        $secondResolver = TransformerFactory::typeSchemaResolver($persistentCache);
        $second = $secondResolver->schema($property);

        $this->assertEquals($first, $second);
        $this->assertSame($first->schema, $second->schema);
        $this->assertGreaterThan(1, $persistentCache->getCalls);
    }
}
