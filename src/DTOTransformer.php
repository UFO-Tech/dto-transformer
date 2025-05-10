<?php

namespace Ufo\DTO;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Interfaces\IArrayConvertible;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\IDTOToArrayTransformer;
use Ufo\DTO\Helpers\TypeHintResolver;

use function array_key_exists;
use function array_map;
use function gettype;

class DTOTransformer extends BaseDTOFromArrayTransformer implements IDTOToArrayTransformer,  IDTOFromArrayTransformer
{

    /**
     * Converts a DTO object to an associative array.
     *
     * @param object $dto The object to convert.
     * @return array An associative array of the object's properties.
     */
    public static function toArray(object $dto): array
    {
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties();
        $array = [];

        foreach ($properties as $property) {
            $value = $property->getValue($dto);
            $value = static::convertValue($value);
            $array[$property->getName()] = $value;
        }

        return $array;
    }

    protected static function convertValue(mixed $value): mixed
    {
        return match (gettype($value)) {
            TypeHintResolver::ARRAY->value => static::mapArrayWithKeys($value),
            TypeHintResolver::OBJECT->value => $value instanceof IArrayConvertible ? $value->toArray() : static::toArray($value),
            default => $value,
        };
    }

    protected static function mapArrayWithKeys(array $array): array
    {
        return array_map(fn($v) => static::convertValue($v), $array);
    }

    /**
     * @throws BadParamException
     * @throws ReflectionException
     */
    protected static function transformFromArray(string $classFQCN, array $data, array $renameKey = []): object
    {
        $instance = null;
        $reflectionClass = new ReflectionClass($classFQCN);
        $constructParams = [];
        $hasReadonly = false;
        $constructor = $reflectionClass->getConstructor();

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $param) {
                $key = static::getPropertyKey($param, $renameKey);
                if (!$key) continue;
                $constructParams[$key] = static::extractValue($key, $data, $param, $classFQCN);
                try {
                    if ($reflectionClass->getProperty($key)->isReadOnly()) {
                        $hasReadonly = true;
                    }
                } catch (\Throwable) {}
            }
            if ($hasReadonly) $instance = $reflectionClass->newInstanceArgs($constructParams);
        }
        $instance = $instance ?? $reflectionClass->newInstanceWithoutConstructor();

        foreach ($reflectionClass->getProperties() as $property) {
            $key = static::getPropertyKey($property, $renameKey);

            if (!$key || $property->isReadOnly() || ($hasReadonly && array_key_exists($key, $constructParams))) {
                continue;
            }

            $value = static::extractValue($key, $data, $property, $classFQCN);
            $property->setValue($instance, $value);
        }

        return $instance;
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return true;
    }

    /**
     * @throws ReflectionException
     * @throws BadParamException
     */
    protected static function extractValue(
        string $key,
        array $data,
        ReflectionParameter|ReflectionProperty $ref,
        string $classFQCN
    ): mixed {
        if (isset($data[$key])) {
            return static::checkAttributes($ref, $data[$key]);
        }

        return match (true) {
            $ref instanceof ReflectionParameter => $ref->isOptional()
                ? $ref->getDefaultValue()
                : throw new InvalidArgumentException("Missing required key for constructor param: '$key'"),

            $ref instanceof ReflectionProperty => (function () use ($classFQCN, $ref, $key) {
                $refClass = (new ReflectionClass($classFQCN));
                $instance = $refClass->newInstanceWithoutConstructor();
                try {
                    return $ref->getValue($instance);
                } catch (\Throwable) {
                    if (!$ref->isInitialized($instance)) {
                        foreach ($refClass->getConstructor()->getParameters() as $p) {
                            if ($p->getName() === $key && $p->isOptional()) {
                                return $p->getDefaultValue();
                            }
                        }
                    }
                    throw new InvalidArgumentException("Missing required key for property: '$key'");
                }
            })(),

            default => throw new InvalidArgumentException('Unsupported reflection type'),
        };
    }

    /**
     * @throws BadParamException
     */
    protected static function checkAttributes(ReflectionProperty|ReflectionParameter $property, mixed $value): mixed
    {
        $attributes = $property->getAttributes();
        foreach ($attributes as $attributeDefinition) {
            if (!isset($attributeDefinition->name)) continue;
            try {
                $value = DTOAttributesEnum::tryFromAttr($attributeDefinition, $value, $property);
            } catch (\ValueError) {}
        }
        return $value;
    }

    protected static function getPropertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): ?string
    {
        $pName = $property->getName();
        return array_key_exists($pName, $renameKey) ? $renameKey[$pName] : $pName;
    }
}