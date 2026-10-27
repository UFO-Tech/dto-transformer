<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\Schema\ClassSchema;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Tests\Fixtures\DTO\CallerNamespaceDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Fixtures\DTO\UserOrDummyDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;
use Ufo\DTO\Transformer\Hydrator\Cache\CachedCompiledTypeSchemaProvider;
use Ufo\DTO\Transformer\Hydrator\Cache\CachedHydratorChain;
use Ufo\DTO\Transformer\Hydrator\Cache\TypeSchemaSets;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProviderInterface;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;

final class CacheBoundsTest extends TestCase
{
    public function testTypeSchemaProviderKeepsBoundedNumberOfTypeSchemaSets(): void
    {
        $schema = new ClassSchema(new ReflectionClass(DummyDTO::class), [], [], ['$defaultNamespace']);
        $inner = new class implements CompiledTypeSchemaProviderInterface {
            public int $resolved = 0;

            public function typeSchemas(ClassSchema $schema, array $namespaces): array
            {
                $this->resolved++;

                return [];
            }
        };
        $provider = new CachedCompiledTypeSchemaProvider($inner);

        for ($i = 0; $i < 200; $i++) {
            $provider->typeSchemas($schema, ['$defaultNamespace' => 'Ns' . $i]);
        }
        $provider->typeSchemas($schema, ['$defaultNamespace' => 'Ns199']);
        $provider->typeSchemas($schema, ['$defaultNamespace' => 'Ns0']);

        $limit = (new \ReflectionClassConstant(CachedCompiledTypeSchemaProvider::class, 'TYPE_SCHEMA_SETS_LIMIT'))->getValue();
        $this->assertCount($limit, $this->typeSchemaSets($provider, $schema)->sets);
        $this->assertSame(200, $inner->resolved);
    }

    public function testFingerprintSkipsNamespacesNativeTypesDoNotRead(): void
    {
        $transformer = TransformerFactory::fromArrayTransformer();
        for ($i = 0; $i < 50; $i++) {
            $namespaces = ['$defaultNamespace' => 'Ns' . $i, 'Item' => 'Ns' . $i . '\\Item'];
            $transformer->transformFromArray(UserOrDummyDTO::class, ['item' => ['id' => 1, 'name' => 'n']], namespaces: $namespaces);
            $transformer->transformFromArray(CallerNamespaceDTO::class, ['item' => []], namespaces: $namespaces);
        }

        $schemaProvider = (new ReflectionProperty($transformer, 'schemaProvider'))->getValue($transformer);
        $typeSchemaProvider = (new ReflectionProperty($transformer, 'typeSchemaProvider'))->getValue($transformer);
        $native = $schemaProvider->schema(UserOrDummyDTO::class);
        $docblock = $schemaProvider->schema(CallerNamespaceDTO::class);

        $this->assertSame([], $native->namespaceKeys);
        $this->assertSame([], $this->typeSchemaSets($typeSchemaProvider, $native)->sets);
        $this->assertContains('$defaultNamespace', $docblock->namespaceKeys);
        $this->assertContains('Item', $docblock->namespaceKeys);
        $this->assertNotSame([], $this->typeSchemaSets($typeSchemaProvider, $docblock)->sets);
    }

    public function testHydratorChainKeepsBoundedNumberOfSchemas(): void
    {
        $chain = TransformerFactory::hydratorChain();
        $this->assertInstanceOf(HydratorChain::class, $chain);

        for ($i = 0; $i < 5000; $i++) {
            $chain->hydratorFor([TypeHintResolver::CLASS_FQCN => 'Ns' . $i . '\\Item']);
        }

        $this->assertInstanceOf(CachedHydratorChain::class, $chain);
        $limit = (new \ReflectionClassConstant(CachedHydratorChain::class, 'HYDRATOR_CACHE_LIMIT'))->getValue();
        $this->assertCount($limit, (new ReflectionProperty(CachedHydratorChain::class, 'nodeBySchema'))->getValue($chain));
    }

    public function testDefaultFactoryBuildsNewCompositionOnEveryCall(): void
    {
        $this->assertNotSame(DefaultDTOTransformerFactory::default(), DefaultDTOTransformerFactory::default());
    }

    private function typeSchemaSets(CachedCompiledTypeSchemaProvider $provider, ClassSchema $schema): TypeSchemaSets
    {
        return (new ReflectionProperty(CachedCompiledTypeSchemaProvider::class, 'typeSchemaSets'))->getValue($provider)[$schema];
    }
}
