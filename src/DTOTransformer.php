<?php

namespace Ufo\DTO;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
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
        $instance = $instance ?? static::createUninitializedInstance($reflectionClass);

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
        if (array_key_exists($key, $data)) {
            return static::checkAttributes($ref, $data[$key]);
        }

        return match (true) {
            $ref instanceof ReflectionParameter => $ref->isOptional()
                ? $ref->getDefaultValue()
                : throw new InvalidArgumentException("Missing required key for constructor param: '$key'"),

            $ref instanceof ReflectionProperty => (function () use ($classFQCN, $ref, $key) {
                $refClass = (new ReflectionClass($classFQCN));
                $instance = static::createUninitializedInstance($refClass);
                try {
                    return $ref->getValue($instance);
                } catch (\Throwable) {
                    if (!$ref->isInitialized($instance)) {
                        $constructor = $refClass->getConstructor();

                        if ($constructor !== null) {
                            foreach ($constructor->getParameters() as $p) {
                                if ($p->getName() === $key && $p->isOptional()) {
                                    return $p->getDefaultValue();
                                }
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
                $value = DTOAttributesEnum::tryFromAttr($attributeDefinition, $value, $property, static::class);
            } catch (\ValueError) {}
        }

        if (is_array($value)) {
            return static::resolveValueForType($property->getType(), $value, $property);
        }
        return $value;
    }

    protected static function resolveValueForType(
        \ReflectionType|null $type,
        array $value,
        ReflectionProperty|ReflectionParameter $property
    ): mixed
    {
        if ($type instanceof ReflectionUnionType) {
            $allowArray = false;

            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType && $subType->getName() === TypeHintResolver::ARRAY->value) {
                    $allowArray = true;
                    continue;
                }

                try {
                    return static::resolveValueForType($subType, $value, $property);
                } catch (BadParamException) {}
            }

            if ($allowArray) {
                return $value;
            }
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return static::tryTransformToMatchingClass($type->getName(), $value);
        }

        if ($type instanceof ReflectionNamedType && $type->getName() === TypeHintResolver::ARRAY->value) {
            return $value;
        }

        throw new BadParamException(sprintf("Cannot assign array to property %s::\$%s of type %s",
            $property->getDeclaringClass()->getName(),
            $property->getName(),
            $property->getType()
        ));
    }

    protected static function tryTransformToMatchingClass(string $classFQCN, array $data): mixed
    {
        if (!TypeHintResolver::isRealClass($classFQCN)) {
            throw new BadParamException(sprintf('Class %s does not exist or is not instantiable', $classFQCN));
        }

        if (!static::doesArrayMatchClass($classFQCN, $data)) {
            throw new BadParamException(sprintf('Cannot assign array to %s', $classFQCN));
        }

        try {
            return static::transformFromArray($classFQCN, $data);
        } catch (\Throwable $e) {
            throw new BadParamException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected static function doesArrayMatchClass(string $classFQCN, array $data): bool
    {
        try {
            $reflection = new \ReflectionClass($classFQCN);

            if (!TypeHintResolver::isRealClass($classFQCN)) {
                return false;
            }

            $constructor = $reflection->getConstructor();
            $hasReadonly = static::checkReadonlyInClass($reflection);

            if ($constructor && $constructor->isPublic()) {
                if (!static::constructorParamsMatch($constructor, $data)) {
                    return false;
                }

                if ($hasReadonly) {
                    return true;
                }
            }

            return static::propertiesMatch($reflection, $data);
        } catch (\ReflectionException|\Throwable) {
            return false;
        }
    }

    protected static function constructorParamsMatch(\ReflectionMethod $constructor, array $data): bool
    {
        foreach ($constructor->getParameters() as $param) {
            $keys = static::getPropertyKey($param, []);
            if (!$keys->dataKey || $param->isOptional()) continue;

            if (static::isKeyMissing($data, $keys->dataKey)) {
                return false;
            }
        }

        return true;
    }

    protected static function propertiesMatch(\ReflectionClass $reflection, array $data): bool
    {
        $instance = static::createUninitializedInstance($reflection);

        foreach ($reflection->getProperties() as $property) {
            if ($property->isReadOnly()) continue;

            $keys = static::getPropertyKey($property, []);
            if (!$keys->dataKey) continue;

            $isUninitialized = !$property->hasDefaultValue() && !$property->isInitialized($instance);
            if ($isUninitialized && static::isKeyMissing($data, $keys->dataKey)) {
                return false;
            }
        }

        return true;
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

    protected static function isKeyMissing(array $data, string $key): bool
    {
        return !array_key_exists($key, $data);
    }

    protected static function createUninitializedInstance(string|ReflectionClass $class): object
    {
        $reflection = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);
        return $reflection->newInstanceWithoutConstructor();
    }
}