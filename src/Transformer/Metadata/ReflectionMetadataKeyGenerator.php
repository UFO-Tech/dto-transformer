<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use function md5;
use function serialize;

class ReflectionMetadataKeyGenerator implements ReflectionMetadataKeyGeneratorInterface
{
    protected const string CLASS_PROPERTY_SEPARATOR = '::$';
    protected const string FUNCTION_PARAMETER_SEPARATOR = ':$';
    protected const string SCHEMA_KEY_SEPARATOR = ':';
    protected const string PROPERTY_SCHEMA_PREFIX = 'property:';
    protected const string PARAMETER_SCHEMA_PREFIX = 'parameter:';
    protected const string NULLABLE_TRUE = '1';
    protected const string NULLABLE_FALSE = '0';

    public function classKey(ReflectionClass|string $class): string
    {
        return $class instanceof ReflectionClass ? $class->getName() : $class;
    }

    public function propertyKey(ReflectionProperty $property): string
    {
        return $property->getDeclaringClass()->getName() . static::CLASS_PROPERTY_SEPARATOR . $property->getName();
    }

    public function parameterKey(ReflectionParameter $parameter): string
    {
        return $this->functionKey($parameter->getDeclaringFunction()) . static::FUNCTION_PARAMETER_SEPARATOR . $parameter->getName();
    }

    public function functionKey(ReflectionFunctionAbstract $function): string
    {
        if ($function instanceof ReflectionMethod) {
            return $function->getDeclaringClass()->getName() . '::' . $function->getName();
        }

        return $function->getName();
    }

    public function namespacesKey(array $namespaces): string
    {
        ksort($namespaces);

        return md5(serialize($namespaces));
    }

    public function reflectionSchemaKey(
        ReflectionProperty|ReflectionParameter|ReflectionType $reflection,
        array $namespaces = [],
    ): string
    {
        $namespaceKey = $this->namespacesKey($namespaces);

        if ($reflection instanceof ReflectionProperty) {
            return static::PROPERTY_SCHEMA_PREFIX . $this->propertyKey($reflection) . static::SCHEMA_KEY_SEPARATOR . $namespaceKey;
        }

        if ($reflection instanceof ReflectionParameter) {
            return static::PARAMETER_SCHEMA_PREFIX . $this->parameterKey($reflection) . static::SCHEMA_KEY_SEPARATOR . $namespaceKey;
        }

        return $this->nativeTypeKey($reflection, $namespaces);
    }

    public function nativeTypeKey(ReflectionType $type, array $namespaces = []): string
    {
        return (string) $type
            . static::SCHEMA_KEY_SEPARATOR
            . ($type->allowsNull() ? static::NULLABLE_TRUE : static::NULLABLE_FALSE)
            . static::SCHEMA_KEY_SEPARATOR
            . $this->namespacesKey($namespaces);
    }
}
