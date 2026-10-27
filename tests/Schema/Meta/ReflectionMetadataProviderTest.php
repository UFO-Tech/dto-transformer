<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Schema\Meta;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Ufo\DTO\Tests\Fixtures\DTO\DocblockDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;

class ReflectionMetadataProviderTest extends TestCase
{
    public function testItReusesReflectionPropertiesWithinProviderInstance(): void
    {
        $provider = TransformerFactory::metadataProvider();

        $first = $provider->reflectionProperties(DummyDTO::class);
        $second = $provider->reflectionProperties(new ReflectionClass(DummyDTO::class));

        $this->assertSame($first, $second);
        $this->assertSame(['id', 'name'], array_map(static fn (\ReflectionProperty $property): string => $property->getName(), $first));
    }

    public function testItReturnsConstructorParametersByNameOncePerClass(): void
    {
        $provider = TransformerFactory::metadataProvider();

        $parameters = $provider->constructorParameters(DummyDTO::class);

        $this->assertSame(['id', 'name'], array_keys($parameters));
        $this->assertSame('id', $parameters['id']->getName());
        $this->assertSame($parameters['id'], $provider->constructorParameters(new ReflectionClass(DummyDTO::class))['id']);
        $this->assertSame([], $provider->constructorParameters(\stdClass::class));
    }

    public function testItParsesDeclaringFunctionDocBlockOnceThroughMetadata(): void
    {
        $provider = TransformerFactory::metadataProvider();
        $parameter = (new ReflectionClass(DocblockDTO::class))
            ->getConstructor()
            ->getParameters()[1];

        $tags = $provider->docBlock($parameter->getDeclaringFunction())->getTagsByName('param');

        $this->assertNotEmpty($tags);
        $this->assertSame('collection', $tags[0]->getVariableName());
    }

    public function testItExposesDeclaringNamespacesForResolvers(): void
    {
        $provider = TransformerFactory::metadataProvider();
        $class = new ReflectionClass(DocblockDTO::class);

        $namespaces = $provider->declaringNamespaces($class);

        $this->assertSame('Ufo\DTO\Tests\Fixtures\Enum\IntEnum', $namespaces['IntEnum']);
        $this->assertSame('Ufo\DTO\Tests\Fixtures\DTO', $namespaces['$defaultNamespace']);
    }
}
