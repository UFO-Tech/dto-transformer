<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\VO\ResolvedTypeSchemaVO;

final class StaticInstanceIsolationTypeSchemaResolver implements TypeSchemaResolverInterface
{
    public function schema(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): ResolvedTypeSchemaVO
    {
        return new ResolvedTypeSchemaVO(['type' => 'string'], false, true);
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
}
