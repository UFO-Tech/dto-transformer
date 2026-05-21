<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Meta;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

interface ReflectionMetadataKeyGeneratorInterface
{
    public function classKey(ReflectionClass|string $class): string;

    public function propertyKey(ReflectionProperty $property): string;

    public function parameterKey(ReflectionParameter $parameter): string;

    public function functionKey(ReflectionFunctionAbstract $function): string;

    /**
     * @param array<string, string>|string[] $namespaces
     */
    public function namespacesKey(array $namespaces): string;

    /**
     * @param array<string, string>|string[] $namespaces
     */
    public function reflectionSchemaKey(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): string;

    /**
     * @param array<string, string>|string[] $namespaces
     */
    public function nativeTypeKey(ReflectionType $type, array $namespaces = []): string;
}

