<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Transformer\Metadata\ClassMetadata;
use Ufo\DTO\Transformer\Metadata\DocBlockMetadata;
use Ufo\DTO\Transformer\Metadata\ParameterMetadata;
use Ufo\DTO\Transformer\Metadata\PropertyMetadata;

final class StaticInstanceIsolationReflectionMetadataProvider implements ReflectionMetadataProviderInterface
{
    public function classMetadata(ReflectionClass|string $class): ClassMetadata
    {
        $reflection = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);

        return new ClassMetadata($reflection, [], $this->emptyDocBlock());
    }

    public function properties(ReflectionClass|string $class): array
    {
        $properties = [];

        foreach ($this->reflectionProperties($class) as $property) {
            $properties[$property->getName()] = $this->propertyMetadata($property);
        }

        return $properties;
    }

    public function reflectionProperties(ReflectionClass|string $class): array
    {
        $reflection = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);

        return $reflection->getProperties();
    }

    public function propertyMetadata(ReflectionProperty $property): PropertyMetadata
    {
        return new PropertyMetadata($property, [], $this->emptyDocBlock());
    }

    public function parameterMetadata(ReflectionParameter $parameter): ParameterMetadata
    {
        return new ParameterMetadata($parameter, [], $this->emptyDocBlock());
    }

    public function promotedConstructorParameter(
        ReflectionProperty $property,
        ReflectionClass|string $class,
    ): ?ParameterMetadata {
        return null;
    }

    public function declaringNamespaces(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): array
    {
        return [];
    }

    public function docBlock(ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $reflection): DocBlockMetadata
    {
        return $this->emptyDocBlock();
    }

    public function attributes(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name = null,
        int $flags = 0,
    ): array {
        return [];
    }

    private function emptyDocBlock(): DocBlockMetadata
    {
        return new DocBlockMetadata(null);
    }
}
