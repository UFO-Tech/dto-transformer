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
use Ufo\DTO\VO\TransformKeyVO;
use Symfony\Component\Serializer\Attribute\Ignore;

use function array_key_exists;
use function array_map;
use function gettype;
use function is_object;

class DTOTransformer extends BaseDTOFromArrayTransformer implements IDTOToArrayTransformer,  IDTOFromArrayTransformer
{

    /**
     * Converts a DTO object to an associative array.
     *
     * @param object $dto The object to convert.
     * @param array<string,string|null> $renameKey
     *
     * @return array An associative array of the object's properties.
     */
    public static function toArray(object $dto, array $renameKey = []): array
    {
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties();
        $array = [];

        foreach ($properties as $property) {
            $keys = static::getPropertyKey($property, $renameKey);
            if (!$keys->dataKey) continue;
            $value = $property->getValue($dto);
            $value = static::convertValue($value);
            $array[$keys->dataKey] = $value;
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
        $hasReadonly = static::checkReadonlyInClass($reflectionClass);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $param) {
                $keys = static::getPropertyKey($param, $renameKey);
                if (!$keys->dataKey) continue;
                $constructParams[$keys->dtoKey] = static::extractValue($keys->dataKey, $data, $param, $classFQCN);
            }
            if ($hasReadonly) $instance = $reflectionClass->newInstanceArgs($constructParams);
        }
        $instance = $instance ?? $reflectionClass->newInstanceWithoutConstructor();

        foreach ($reflectionClass->getProperties() as $property) {
            $keys = static::getPropertyKey($property, $renameKey);

            if (!$keys->dataKey || $property->isReadOnly() || ($hasReadonly && array_key_exists($keys->dtoKey, $constructParams))) {
                continue;
            }

            $value = static::extractValue($keys->dataKey, $data, $property, $classFQCN);
            $property->setValue($instance, $value);
        }

        return $instance;
    }

    protected static function checkReadonlyInClass(ReflectionClass $reflectionClass): bool
    {
        $hasReadonly = false;
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isReadOnly()) {
                $hasReadonly = true;
            }
        }
        return $hasReadonly;
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
        try {
            if (TypeHintResolver::isRealClass($property->getType()->getName()) && !is_object($value)) {
                $value = static::transformFromArray($property->getType()->getName(), $value);
            }
        } catch (\Throwable) {}
        return $value;
    }

    protected static function getPropertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): TransformKeyVO
    {
        $dtoKey = $property->getName();
        $dataKey = array_key_exists($dtoKey, $renameKey) ? $renameKey[$dtoKey] : $dtoKey;
        if ($property->getAttributes(Ignore::class)[0] ?? null) {
            $dataKey = null;
        }
        return new TransformKeyVO($dtoKey, $dataKey);
    }
}