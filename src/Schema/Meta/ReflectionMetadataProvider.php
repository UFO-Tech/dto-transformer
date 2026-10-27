<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Schema\Type\TypeHintResolver;

/**
 * Метадані з reflection без кешу; кешує {@see Cache\CachedReflectionMetadataProvider}.
 */
class ReflectionMetadataProvider implements ReflectionMetadataProviderInterface
{
    public function __construct(
        protected DocBlockParserInterface $docBlockParser,
    ) {}

    public function reflectionProperties(ReflectionClass|string $class): array
    {
        return $this->reflectionClass($class)->getProperties();
    }

    public function constructorParameters(ReflectionClass|string $class): array
    {
        $parameters = [];
        foreach ($this->reflectionClass($class)->getConstructor()?->getParameters() ?? [] as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        return $parameters;
    }

    public function declaringNamespaces(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): array
    {
        $class = ReflectionDeclaringClass::of($reflection);

        return $class === null ? [] : TypeHintResolver::getUsesNamespaces($class->getName());
    }

    public function docBlock(ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $reflection): DocBlockMetadata
    {
        return $this->docBlockParser->parse($reflection->getDocComment() ?: null);
    }

    public function attributes(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name = null,
        int $flags = 0,
    ): array
    {
        return $name === null ? $reflection->getAttributes() : $reflection->getAttributes($name, $flags);
    }

    protected function reflectionClass(ReflectionClass|string $class): ReflectionClass
    {
        return $class instanceof ReflectionClass ? $class : new ReflectionClass($class);
    }
}
