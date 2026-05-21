<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Meta;

use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Ufo\DTO\VO\ResolvedTypeSchemaVO;

interface TypeSchemaResolverInterface
{
    /**
     * @param array<string, string> $namespaces
     */
    public function schema(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): ResolvedTypeSchemaVO;

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
}
