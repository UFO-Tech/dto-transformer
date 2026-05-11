<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Attributes\StrictMode;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;
use Ufo\DTO\Interfaces\Meta\StrictModeResolverInterface;
use function count;
use function in_array;
use function preg_split;
use function str_starts_with;
use function strtolower;
use function trim;

class StrictModeResolver implements StrictModeResolverInterface
{
    protected const string PREFIXED_KEY_FORMAT = '%s%s';
    protected const string KEY_FORMAT = '%s%s%s';
    protected const string TAG_NAME = 'strictMode';
    protected const string CACHE_STRICT_MODE_PREFIX = 'strict_mode.';
    protected const string CACHE_DEFAULT_TRUE_SUFFIX = '.1';
    protected const string CACHE_DEFAULT_FALSE_SUFFIX = '.0';
    protected const string CACHE_PROPERTY_PREFIX = 'property.';
    protected const string CACHE_PARAMETER_PREFIX = 'parameter.';
    protected const string DOCBLOCK_PARAMETER_PREFIX = '$';
    protected const string DOCBLOCK_SPLIT_PATTERN = '/\s+/';
    protected const array TRUE_VALUES = ['1', 'true', 'yes', 'on'];

    public function __construct(
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected ReflectionMetadataKeyGeneratorInterface $keyGenerator,
        protected RuntimeReflectionCacheInterface $runtimeCache,
    ) {}

    public function resolve(
        ReflectionProperty|ReflectionParameter $reflection,
        bool $default = false,
    ): bool
    {
        return $this->runtimeCache->rememberPersistent(
            sprintf(
                static::KEY_FORMAT,
                static::CACHE_STRICT_MODE_PREFIX,
                $this->cacheKey($reflection),
                $default ? static::CACHE_DEFAULT_TRUE_SUFFIX : static::CACHE_DEFAULT_FALSE_SUFFIX,
            ),
            fn (): bool => $this->resolveUncached($reflection, $default),
        );
    }

    protected function resolveUncached(
        ReflectionProperty|ReflectionParameter $reflection,
        bool $default,
    ): bool
    {
        if ($reflection instanceof ReflectionProperty) {
            return $this->resolveProperty($reflection, $default);
        }

        return $this->resolveParameter($reflection, $default);
    }

    protected function resolveProperty(ReflectionProperty $property, bool $default): bool
    {
        $attributeValue = $this->attributeValue($property);
        if ($attributeValue !== null) {
            return $attributeValue;
        }

        $docblockValue = $this->docblockValue($this->metadataProvider->docBlock($property));
        if ($docblockValue !== null) {
            return $docblockValue;
        }

        return $this->resolveClassHierarchy($property->getDeclaringClass(), $default);
    }

    protected function resolveParameter(ReflectionParameter $parameter, bool $default): bool
    {
        $attributeValue = $this->attributeValue($parameter);
        if ($attributeValue !== null) {
            return $attributeValue;
        }

        $function = $parameter->getDeclaringFunction();
        $docblockValue = $this->docblockValue(
            $this->metadataProvider->docBlock($function),
            $parameter->getName(),
        );
        if ($docblockValue !== null) {
            return $docblockValue;
        }

        $functionAttributeValue = $this->attributeValue($function);
        if ($functionAttributeValue !== null) {
            return $functionAttributeValue;
        }

        $functionDocblockValue = $this->docblockValue($this->metadataProvider->docBlock($function));
        if ($functionDocblockValue !== null) {
            return $functionDocblockValue;
        }

        if ($function instanceof ReflectionMethod) {
            return $this->resolveClassHierarchy($function->getDeclaringClass(), $default);
        }

        return $default;
    }

    protected function resolveClassHierarchy(ReflectionClass $class, bool $default): bool
    {
        $current = $class;

        do {
            $attributeValue = $this->attributeValue($current);
            if ($attributeValue !== null) {
                return $attributeValue;
            }

            $docblockValue = $this->docblockValue($this->metadataProvider->docBlock($current));
            if ($docblockValue !== null) {
                return $docblockValue;
            }

            foreach ($current->getInterfaces() as $interface) {
                $interfaceValue = $this->resolveClassDeclaration($interface);
                if ($interfaceValue !== null) {
                    return $interfaceValue;
                }
            }

            $current = $current->getParentClass();
        } while ($current instanceof ReflectionClass);

        return $default;
    }

    protected function resolveClassDeclaration(ReflectionClass $class): ?bool
    {
        $attributeValue = $this->attributeValue($class);
        if ($attributeValue !== null) {
            return $attributeValue;
        }

        return $this->docblockValue($this->metadataProvider->docBlock($class));
    }

    protected function attributeValue(
        ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection,
    ): ?bool {
        $attribute = $this->metadataProvider->attributes($reflection, StrictMode::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        return $attribute?->newInstance()->enabled;
    }

    protected function docblockValue(DocBlockMetadata $docBlock, ?string $parameterName = null): ?bool
    {
        if ($docBlock->isEmpty()) {
            return null;
        }

        foreach ($docBlock->getTagsByName(self::TAG_NAME) as $tag) {
            $value = trim((string) $tag);
            if ($value === '') {
                return true;
            }

            $parts = preg_split(static::DOCBLOCK_SPLIT_PATTERN, $value) ?: [];
            if (count($parts) === 1) {
                return $this->boolValue($parts[0]);
            }
            if (!$parameterName) continue;

            $tagParameterName = $parts[0];
            if (str_starts_with($tagParameterName, static::DOCBLOCK_PARAMETER_PREFIX)) {
                $tagParameterName = substr($tagParameterName, 1);
            }

            if ($tagParameterName === $parameterName) {
                return $this->boolValue($parts[1] ?? '');
            }
        }

        return null;
    }

    protected function boolValue(string $value): bool
    {
        return in_array(strtolower(trim($value)), static::TRUE_VALUES, true);
    }

    protected function cacheKey(ReflectionProperty|ReflectionParameter $reflection): string
    {
        if ($reflection instanceof ReflectionProperty) {
            return sprintf(static::PREFIXED_KEY_FORMAT, static::CACHE_PROPERTY_PREFIX, $this->keyGenerator->propertyKey($reflection));
        }

        return sprintf(static::PREFIXED_KEY_FORMAT, static::CACHE_PARAMETER_PREFIX, $this->keyGenerator->parameterKey($reflection));
    }
}
