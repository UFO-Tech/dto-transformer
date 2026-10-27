<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Type;

use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

interface TypeSchemaResolverInterface
{
    /**
     * @param array<string, string> $namespaces
     */
    public function schema(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): ResolvedTypeSchema;

    /**
     * @param array<string, string> $namespaces
     */
    public function docTypeSchema(
        ReflectionProperty|ReflectionParameter $reflection,
        array $namespaces = [],
    ): array;

    /**
     * @param array<string, string> $namespaces
     */
    public function nativeTypeSchema(?ReflectionType $type, array $namespaces = []): array;

    /**
     * @return array<string, string>
     */
    public function getDeclaringNamespaces(ReflectionProperty|ReflectionParameter $reflection): array;

    public function source(ReflectionProperty|ReflectionParameter $reflection): TypeSource;

    /**
     * @param array<string, string> $namespaces
     */
    public function fromSource(TypeSource $source, array $namespaces = []): ResolvedTypeSchema;
}
