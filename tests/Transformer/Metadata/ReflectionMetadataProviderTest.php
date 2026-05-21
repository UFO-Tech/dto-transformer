<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer\Metadata;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Ufo\DTO\Tests\Fixtures\DTO\DocblockDTO;
use Ufo\DTO\Tests\Fixtures\DTO\DummyDTO;
use Ufo\DTO\Tests\Support\TransformerFactory;

class ReflectionMetadataProviderTest extends TestCase
{
    public function testItReusesClassMetadataWithinProviderInstance(): void
    {
        $provider = TransformerFactory::metadataProvider();

        $first = $provider->classMetadata(DummyDTO::class);
        $second = $provider->classMetadata(DummyDTO::class);

        $this->assertSame($first, $second);
        $this->assertSame(DummyDTO::class, $first->reflection->getName());
    }

    public function testItReturnsPromotedConstructorParameterMetadata(): void
    {
        $provider = TransformerFactory::metadataProvider();
        $class = new ReflectionClass(DummyDTO::class);

        $metadata = $provider->promotedConstructorParameter($class->getProperty('id'), $class);

        $this->assertNotNull($metadata);
        $this->assertSame('id', $metadata->reflection->getName());
    }

    public function testItParsesDeclaringFunctionDocBlockOnceThroughMetadata(): void
    {
        $provider = TransformerFactory::metadataProvider();
        $parameter = (new ReflectionClass(DocblockDTO::class))
            ->getConstructor()
            ->getParameters()[1];

        $metadata = $provider->parameterMetadata($parameter);
        $tags = $metadata->declaringFunctionDocBlock->getTagsByName('param');

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
