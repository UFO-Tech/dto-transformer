<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;

interface ReflectionMetadataProviderInterface
{
    /**
     * @return ReflectionProperty[]
     */
    public function reflectionProperties(ReflectionClass|string $class): array;

    /**
     * @return array<string, ReflectionParameter> за іменем параметра; порожній, якщо конструктора немає
     */
    public function constructorParameters(ReflectionClass|string $class): array;

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
