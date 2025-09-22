<?php

namespace Ufo\DTO\Helpers;

use InvalidArgumentException;
use ReflectionException;
use Ufo\DTO\VO\EnumVO;
use Ufo\DTO\Helpers\TypeHintResolver as T;

use function call_user_func;

enum EnumResolver:string
{
    case STRING = T::STRING->value;
    case INT = T::INT->value;

    const string CORE = 'x-ufo';
    const string ENUM = self::CORE . '-enum';
    const string ASSERTIONS = self::CORE . '-assertions';

    const string ENUM_NAME = 'name';
    const string METHOD_VALUES = 'values';
    const string METHOD_FROM_VALUE = 'fromValue';
    const string METHOD_TRY_FROM_VALUE = 'tryFromValue';
    const string ENUM_KEY = 'enum';

    /**
     * @param class-string $enumFQCN
     * @throws ReflectionException|InvalidArgumentException
     */
    public static function generateEnumSchema(
        string $enumFQCN,
        string $methodGetValues = self::METHOD_VALUES,
        string $methodTryFrom = self::METHOD_TRY_FROM_VALUE,
    ): array
    {
        $refEnum = new \ReflectionEnum($enumFQCN);

        $data = array_column($enumFQCN::cases(), 'value', 'name');
        if (method_exists($enumFQCN, $methodGetValues) && method_exists($enumFQCN, $methodTryFrom)) {
            foreach (call_user_func([$enumFQCN, $methodGetValues]) as $value) {
                $enum = call_user_func([$enumFQCN, $methodTryFrom], $value) ?? throw new InvalidArgumentException('Invalid value "' . $value . '" for enum ' . $enumFQCN);
                $data[$enum->name] = $value;
            }
        }

        return [
            T::TYPE => T::phpToJsonSchema($refEnum->getBackingType()->getName()),
            self::ENUM => [
                self::ENUM_NAME => $refEnum->getShortName(),
                self::METHOD_VALUES => $data,
            ],
            self::ENUM_KEY => array_values($data)
        ];
    }

    public static function findEnumNameInJsonSchema(array $type): ?string
    {
        return $type[EnumResolver::ENUM][EnumResolver::ENUM_NAME] ?? null;
    }

    public static function schemaHasEnum(array $schema): bool
    {
        $hasEnum = false;
        foreach ($schema[T::ONE_OFF] ?? [] as $type) {
            $hasEnum = self::schemaHasEnum($type);
            if ($hasEnum) break;
        }

        return $hasEnum || (bool)(
            $schema[T::ITEMS][self::ENUM]
            ?? $schema[T::ITEMS][self::ENUM_KEY]
            ?? $schema[self::ENUM]
            ?? $schema[self::ENUM_KEY]
            ?? false
        );
    }

    public static function enumDataFromSchema(array $schema, ?string $paramName = null): EnumVO
    {
        if ($schema[T::ONE_OFF] ?? false) {
            foreach ($schema[T::ONE_OFF] as $type) {
                if (($type[EnumResolver::ENUM] ?? false) 
                    || ($type[EnumResolver::ENUM_KEY] ?? false) 
                    || ($type[T::ITEMS] ?? false)
                ) {
                    return self::enumDataFromSchema($type, $paramName);
                }
            }
        } elseif (($schema[T::ITEMS][EnumResolver::ENUM] ?? false) || ($schema[T::ITEMS][EnumResolver::ENUM_KEY] ?? false)) {
            return self::enumDataFromSchema($schema[T::ITEMS], $paramName);
        }
        return EnumVO::fromSchema($schema, $paramName);
    }

    public static function applyEnumFqcnToJsonSchema(
        string $enumFQCN,
        array $jsonSchema,
        string|array $paramType,
        string $methodGetValues = self::METHOD_VALUES,
        string $methodTryFrom = self::METHOD_TRY_FROM_VALUE,
    ): array
    {
        $enumSchema = self::generateEnumSchema($enumFQCN, $methodGetValues, $methodTryFrom);
        return self::applyEnumSchemaToJsonSchema($enumSchema, $jsonSchema, $paramType);
    }

    public static function applyEnumSchemaToJsonSchema(
        array $enumSchema,
        array $jsonSchema,
        string|array $paramType
    ): array
    {
        $enumType = $enumSchema[T::TYPE] ?? null;
        $enumVals = $enumSchema[EnumResolver::ENUM_KEY] ?? null;

        if ($paramType === T::ARRAY->value || (($jsonSchema[T::TYPE] ?? null) === T::ARRAY->value)) {
            $items = $jsonSchema[T::ITEMS] ?? [];

            if (!isset($items[T::ONE_OFF]) || !self::injectEnumIntoOneOf($items[T::ONE_OFF], $enumType, $enumVals)) {
                $items = array_replace($items, $enumSchema);
            }

            $jsonSchema[T::TYPE]  = T::ARRAY->value;
            $jsonSchema[T::ITEMS] = $items;
            return $jsonSchema;
        }

        if (
            (isset($jsonSchema[T::ONE_OFF]) || is_array($paramType))
            && (isset($jsonSchema[T::ONE_OFF]) && self::injectEnumIntoOneOf(
                    $jsonSchema[T::ONE_OFF],
                    $enumType,
                    $enumVals
                ))
        ) {
            return $jsonSchema;
        }

        return array_replace($jsonSchema, $enumSchema);
    }

    protected static function injectEnumIntoOneOf(array &$oneOf, ?string $baseType, ?array $enumValues): bool
    {
        if (!$baseType || !$enumValues) return false;

        foreach ($oneOf as &$schema) {
            if (($schema[T::TYPE] ?? null) === $baseType) {
                $schema[EnumResolver::ENUM_KEY] = $enumValues;
                return true;
            }
        }
        return false;
    }

    protected static function getEnumFQCN(string|array $type): ?string
    {
        if (is_array($type)) {
            foreach ($type as $value) {
                if ($res = self::getEnumFQCN($value)) {
                    return $res;
                }
            }
        }

        return (is_string($type) && T::isEnum($type)) ? $type : null;
    }
}
