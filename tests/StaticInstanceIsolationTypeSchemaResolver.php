<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Ufo\DTO\Schema\Type\ResolvedTypeSchema;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;
use Ufo\DTO\Schema\Type\TypeSource;

final class StaticInstanceIsolationTypeSchemaResolver implements TypeSchemaResolverInterface
{
    public function schema(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): ResolvedTypeSchema
    {
        return new ResolvedTypeSchema(['type' => 'string'], false, true);
    }

    public function docTypeSchema(ReflectionProperty|ReflectionParameter $reflection, array $namespaces = []): array
    {
        return [];
    }

    public function nativeTypeSchema(?ReflectionType $type, array $namespaces = []): array
    {
        return ['type' => 'string'];
    }

    public function getDeclaringNamespaces(ReflectionProperty|ReflectionParameter $reflection): array
    {
        return [];
    }

    public function source(ReflectionProperty|ReflectionParameter $reflection): TypeSource
    {
        return new TypeSource('', $reflection->getType(), [], false);
    }

    public function fromSource(TypeSource $source, array $namespaces = []): ResolvedTypeSchema
    {
        return new ResolvedTypeSchema(['type' => 'string'], false, true);
    }
}
