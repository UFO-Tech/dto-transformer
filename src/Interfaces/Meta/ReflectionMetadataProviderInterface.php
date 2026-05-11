<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Meta;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Transformer\Metadata\ClassMetadata;
use Ufo\DTO\Transformer\Metadata\DocBlockMetadata;
use Ufo\DTO\Transformer\Metadata\ParameterMetadata;
use Ufo\DTO\Transformer\Metadata\PropertyMetadata;

interface ReflectionMetadataProviderInterface
{
    public function classMetadata(ReflectionClass|string $class): ClassMetadata;

    /**
     * @return array<string, PropertyMetadata>
     */
    public function properties(ReflectionClass|string $class): array;

    /**
     * @return ReflectionProperty[]
     */
    public function reflectionProperties(ReflectionClass|string $class): array;

    public function propertyMetadata(ReflectionProperty $property): PropertyMetadata;

    public function parameterMetadata(ReflectionParameter $parameter): ParameterMetadata;

    public function promotedConstructorParameter(
        ReflectionProperty $property,
        ReflectionClass|string $class,
    ): ?ParameterMetadata;

    /**
     * @return array<string, string>
     */
    public function declaringNamespaces(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): array;

    public function docBlock(ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $reflection): DocBlockMetadata;

    /**
     * @template T of object
     *
     * @param class-string<T>|null $name
     *
     * @return ReflectionAttribute<T>[]
     */
    public function attributes(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name = null,
        int $flags = 0,
    ): array;
}
