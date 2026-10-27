<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Schema\Meta\DocBlockMetadata;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;

final class StaticInstanceIsolationReflectionMetadataProvider implements ReflectionMetadataProviderInterface
{
    public function reflectionProperties(ReflectionClass|string $class): array
    {
        $reflection = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);

        return $reflection->getProperties();
    }

    public function constructorParameters(ReflectionClass|string $class): array
    {
        $reflection = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);
        $parameters = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        return $parameters;
    }

    public function declaringNamespaces(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): array
    {
        return [];
    }

    public function docBlock(ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $reflection): DocBlockMetadata
    {
        return new DocBlockMetadata(null);
    }

    public function attributes(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name = null,
        int $flags = 0,
    ): array {
        return [];
    }
}
