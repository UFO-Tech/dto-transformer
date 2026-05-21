<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Meta\DocBlockParserInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;

class ReflectionMetadataProvider implements ReflectionMetadataProviderInterface
{
    protected const string KEY_FORMAT = '%s%s';
    protected const string COMPOSITE_KEY_FORMAT = '%s%s%s';
    protected const string ATTRIBUTE_CACHE_KEY_FORMAT = '%s%s%s%s%s';
    protected const string CACHE_DECLARING_NAMESPACES_PREFIX = 'reflection_metadata.declaring_namespaces.';
    protected const string CACHE_DOC_COMMENT_PREFIX = 'reflection_metadata.doc_comment.';
    protected const string DOC_BLOCK_CLASS_PREFIX = 'class.';
    protected const string DOC_BLOCK_PROPERTY_PREFIX = 'property.';
    protected const string DOC_BLOCK_FUNCTION_PREFIX = 'function.';
    protected const string ATTRIBUTE_PARAMETER_PREFIX = 'parameter.';
    protected const string RUNTIME_CLASS_PREFIX = 'runtime.class.';
    protected const string RUNTIME_PROPERTY_PREFIX = 'runtime.property.';
    protected const string RUNTIME_PROPERTIES_PREFIX = 'runtime.properties.';
    protected const string RUNTIME_PARAMETER_PREFIX = 'runtime.parameter.';
    protected const string RUNTIME_PROMOTED_PARAMETER_PREFIX = 'runtime.promoted_parameter.';
    protected const string RUNTIME_NAMESPACES_PREFIX = 'runtime.namespaces.';
    protected const string RUNTIME_DOC_BLOCK_PREFIX = 'runtime.doc_block.';
    protected const string RUNTIME_ATTRIBUTES_PREFIX = 'runtime.attributes.';
    protected const string ATTRIBUTE_NAME_DEFAULT = '*';
    protected const string ATTRIBUTE_KEY_SEPARATOR = ':';
    protected const string PROPERTY_SEPARATOR = '::$';

    public function __construct(
        protected DocBlockParserInterface $docBlockParser,
        protected ReflectionMetadataKeyGeneratorInterface $keyGenerator,
        protected RuntimeReflectionCacheInterface $runtimeCache,
    ) {}

    public function classMetadata(ReflectionClass|string $class): ClassMetadata
    {
        $className = $this->keyGenerator->classKey($class);

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_CLASS_PREFIX, $className),
            fn (): ClassMetadata => $this->createClassMetadata($class),
        );
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
        $reflection = $class instanceof ReflectionClass ? $class : $this->classMetadata($class)->reflection;
        $className = $reflection->getName();

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_PROPERTIES_PREFIX, $className),
            static fn (): array => $reflection->getProperties(),
        );
    }

    public function propertyMetadata(ReflectionProperty $property): PropertyMetadata
    {
        $key = $this->keyGenerator->propertyKey($property);

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_PROPERTY_PREFIX, $key),
            fn (): PropertyMetadata => new PropertyMetadata(
                reflection: $property,
                declaringNamespaces: $this->declaringNamespaces($property),
                docBlock: $this->docBlock($property),
                attributes: $this->attributes($property),
                promotedConstructorParameter: $this->promotedConstructorParameter($property, $property->getDeclaringClass()),
            ),
        );
    }

    public function parameterMetadata(ReflectionParameter $parameter): ParameterMetadata
    {
        $key = $this->keyGenerator->parameterKey($parameter);

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_PARAMETER_PREFIX, $key),
            fn (): ParameterMetadata => new ParameterMetadata(
                reflection: $parameter,
                declaringNamespaces: $this->declaringNamespaces($parameter),
                declaringFunctionDocBlock: $this->docBlock($parameter->getDeclaringFunction()),
                attributes: $this->attributes($parameter),
            ),
        );
    }

    public function promotedConstructorParameter(
        ReflectionProperty $property,
        ReflectionClass|string $class,
    ): ?ParameterMetadata {
        if (!$property->isPromoted()) {
            return null;
        }

        $reflection = $class instanceof ReflectionClass ? $class : $this->classMetadata($class)->reflection;
        $key = sprintf(
            static::COMPOSITE_KEY_FORMAT,
            $reflection->getName(),
            static::PROPERTY_SEPARATOR,
            $property->getName(),
        );
        $parameterName = $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_PROMOTED_PARAMETER_PREFIX, $key),
            fn (): ?string => $this->resolvePromotedConstructorParameterName($property, $reflection),
        );

        if ($parameterName === null) {
            return null;
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return null;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() === $parameterName) {
                return $this->parameterMetadata($parameter);
            }
        }

        return null;
    }

    public function declaringNamespaces(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): array
    {
        $class = $this->declaringClass($reflection);
        if (!$class) {
            return [];
        }

        $key = $class->getName();

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_NAMESPACES_PREFIX, $key),
            fn (): array => $this->runtimeCache()->rememberPersistent(
                $this->cacheKey(static::CACHE_DECLARING_NAMESPACES_PREFIX, $class->getName()),
                static fn (): array => TypeHintResolver::getUsesNamespaces($class->getName()),
            ),
        );
    }

    public function docBlock(ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $reflection): DocBlockMetadata
    {
        $key = match (true) {
            $reflection instanceof ReflectionClass => static::DOC_BLOCK_CLASS_PREFIX . $reflection->getName(),
            $reflection instanceof ReflectionProperty => static::DOC_BLOCK_PROPERTY_PREFIX . $this->keyGenerator->propertyKey($reflection),
            default => static::DOC_BLOCK_FUNCTION_PREFIX . $this->keyGenerator->functionKey($reflection),
        };

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_DOC_BLOCK_PREFIX, $key),
            fn (): DocBlockMetadata => $this->docBlockParser->parse(
                $this->runtimeCache()->rememberPersistent(
                    $this->cacheKey(static::CACHE_DOC_COMMENT_PREFIX, $key),
                    static fn (): ?string => $reflection->getDocComment() ?: null,
                ),
            ),
        );
    }

    public function attributes(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name = null,
        int $flags = 0,
    ): array
    {
        $key = $this->attributeCacheKey($reflection, $name, $flags);

        return $this->runtimeCache()->remember(
            $this->cacheKey(static::RUNTIME_ATTRIBUTES_PREFIX, $key),
            static fn (): array => $name === null
                ? $reflection->getAttributes()
                : $reflection->getAttributes($name, $flags),
        );
    }

    protected function createClassMetadata(ReflectionClass|string $class): ClassMetadata
    {
        $className = $this->keyGenerator->classKey($class);
        $reflection = $class instanceof ReflectionClass && $class->getName() === $className
            ? $class
            : new ReflectionClass($className);

        return new ClassMetadata(
            reflection: $reflection,
            namespaces: $this->declaringNamespaces($reflection),
            docBlock: $this->docBlock($reflection),
            attributes: $this->attributes($reflection),
        );
    }

    protected function declaringClass(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): ?ReflectionClass
    {
        if ($reflection instanceof ReflectionClass) {
            return $reflection;
        }

        if ($reflection instanceof ReflectionProperty) {
            return $reflection->getDeclaringClass();
        }

        $function = $reflection->getDeclaringFunction();
        if ($function instanceof ReflectionMethod) {
            return $function->getDeclaringClass();
        }

        return null;
    }

    protected function resolvePromotedConstructorParameterName(
        ReflectionProperty $property,
        ReflectionClass $reflection,
    ): ?string {
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return null;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() === $property->getName()) {
                return $parameter->getName();
            }
        }

        return null;
    }

    protected function attributeCacheKey(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
        ?string $name,
        int $flags,
    ): string
    {
        $reflectionKey = match (true) {
            $reflection instanceof ReflectionClass => static::DOC_BLOCK_CLASS_PREFIX . $reflection->getName(),
            $reflection instanceof ReflectionProperty => static::DOC_BLOCK_PROPERTY_PREFIX . $this->keyGenerator->propertyKey($reflection),
            $reflection instanceof ReflectionParameter => static::ATTRIBUTE_PARAMETER_PREFIX . $this->keyGenerator->parameterKey($reflection),
            default => static::DOC_BLOCK_FUNCTION_PREFIX . $this->keyGenerator->functionKey($reflection),
        };

        return sprintf(
            static::ATTRIBUTE_CACHE_KEY_FORMAT,
            $reflectionKey,
            static::ATTRIBUTE_KEY_SEPARATOR,
            $name ?? static::ATTRIBUTE_NAME_DEFAULT,
            static::ATTRIBUTE_KEY_SEPARATOR,
            $flags,
        );
    }

    protected function runtimeCache(): RuntimeReflectionCacheInterface
    {
        return $this->runtimeCache;
    }

    protected function cacheKey(string $prefix, string $identifier): string
    {
        return sprintf(static::KEY_FORMAT, $prefix, $identifier);
    }
}
