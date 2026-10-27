<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Type\TypeCompatibility;

/**
 * Значення властивості, коли ключа немає в даних: стратегія визначається один раз на клас, значення — на кожен об'єкт.
 */
class DefaultValueResolver
{
    public function __construct(
        protected TypeCompatibility $typeCompatibility,
        protected ReflectionMetadataProviderInterface $metadataProvider,
    ) {}

    /**
     * Значення на екземплярі без конструктора, інакше необов'язковий однойменний параметр конструктора.
     *
     * @return array{int, mixed, ?ReflectionParameter}
     */
    public function strategy(
        ReflectionProperty $property,
        ReflectionClass $refClass,
        object $probe,
        ReflectionProperty|ReflectionParameter $valueReflection,
    ): array
    {
        if ($property->isStatic() || $property->hasHooks()) {
            return [PropertySchema::DEFAULT_LEGACY, null, null];
        }

        try {
            return [PropertySchema::DEFAULT_PROPERTY, $property->getValue($probe), null];
        } catch (\Throwable) {
        }

        $parameter = $property->isInitialized($probe) ? null : $this->optionalParameter($refClass, $property->getName());
        if ($parameter === null) {
            return [PropertySchema::DEFAULT_MISSING, null, null];
        }

        $parameter = $valueReflection instanceof ReflectionParameter && $valueReflection->getName() === $parameter->getName()
            ? $valueReflection
            : $parameter;

        return [PropertySchema::DEFAULT_PARAMETER, null, $parameter];
    }

    /**
     * @param string $dataKey ключ у даних — для повідомлення про помилку
     * @throws ReflectionException
     */
    public function resolve(PropertySchema $schema, ReflectionClass $refClass, string $dataKey): mixed
    {
        return match ($schema->defaultKind) {
            PropertySchema::DEFAULT_PROPERTY => $schema->defaultValue,
            PropertySchema::DEFAULT_PARAMETER => $this->parameterDefault($schema, $dataKey),
            PropertySchema::DEFAULT_MISSING => throw $this->missingKey($dataKey),
            default => $this->currentValue($schema, $refClass, $dataKey),
        };
    }

    /**
     * Значення властивості на екземплярі без конструктора, інакше значення за замовчуванням параметра конструктора.
     *
     * @throws ReflectionException
     */
    public function currentValue(PropertySchema $schema, ReflectionClass $refClass, string $dataKey): mixed
    {
        $property = $schema->property;
        $instance = $refClass->newInstanceWithoutConstructor();
        try {
            return $property->getValue($instance);
        } catch (\Throwable) {
            $parameter = $property->isInitialized($instance) ? null : $schema->fallbackParameter;
            if ($parameter !== null) {
                $value = $parameter->getDefaultValue();
                if ($this->typeCompatibility->canAssign($value, $property)) return $value;
            }
            throw $this->missingKey($dataKey);
        }
    }

    /**
     * Параметр для `currentValue()`: для ignored — з конструктора класу, що оголошує властивість, інакше — гідрованого класу.
     */
    public function fallbackParameter(ReflectionProperty $property, ReflectionClass $refClass, bool $ignored): ?ReflectionParameter
    {
        return $this->optionalParameter($ignored ? $property->getDeclaringClass() : $refClass, $property->getName());
    }

    protected function optionalParameter(ReflectionClass $refClass, string $name): ?ReflectionParameter
    {
        $parameter = $this->metadataProvider->constructorParameters($refClass)[$name] ?? null;

        return $parameter?->isOptional() ? $parameter : null;
    }

    protected function parameterDefault(PropertySchema $schema, string $dataKey): mixed
    {
        $value = $schema->defaultParameter->getDefaultValue();
        if ($this->typeCompatibility->canAssign($value, $schema->property)) {
            return $value;
        }

        throw $this->missingKey($dataKey);
    }

    protected function missingKey(string $dataKey): InvalidArgumentException
    {
        return new InvalidArgumentException("Missing required key for property: '$dataKey'");
    }
}
