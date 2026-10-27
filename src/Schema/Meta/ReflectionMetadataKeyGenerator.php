<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class ReflectionMetadataKeyGenerator
{
    protected const string CLASS_PROPERTY_SEPARATOR = '::$';
    protected const string FUNCTION_PARAMETER_SEPARATOR = ':$';
    protected const string CLASS_PREFIX = 'class.';
    protected const string PROPERTY_PREFIX = 'property.';
    protected const string PARAMETER_PREFIX = 'parameter.';
    protected const string FUNCTION_PREFIX = 'function.';

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

    /**
     * Ключ будь-якого reflection-об'єкта з префіксом його виду.
     */
    public function reflectionKey(ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflection): string
    {
        return match (true) {
            $reflection instanceof ReflectionClass => static::CLASS_PREFIX . $reflection->getName(),
            $reflection instanceof ReflectionProperty => static::PROPERTY_PREFIX . $this->propertyKey($reflection),
            $reflection instanceof ReflectionParameter => static::PARAMETER_PREFIX . $this->parameterKey($reflection),
            default => static::FUNCTION_PREFIX . $this->functionKey($reflection),
        };
    }

    public function declaringClassKey(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): ?string
    {
        return ReflectionDeclaringClass::of($reflection)?->getName();
    }
}
