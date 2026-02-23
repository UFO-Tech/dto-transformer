<?php

namespace Ufo\DTO\Helpers;

use InvalidArgumentException;
use ReflectionException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
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

        if ($refEnum->isBacked()) {
            /** @var class-string<\BackedEnum> $enumFQCN */
            $data = array_column($enumFQCN::cases(), 'value', 'name');

            if (method_exists($enumFQCN, $methodGetValues) && method_exists($enumFQCN, $methodTryFrom)) {
                foreach (call_user_func([$enumFQCN, $methodGetValues]) as $value) {
                    $enum = call_user_func([$enumFQCN, $methodTryFrom], $value)
                            ?? throw new InvalidArgumentException('Invalid value "' . $value . '" for enum ' . $enumFQCN);

                    $data[$enum->name] = $value;
                }
            }

            $jsonType = T::phpToJsonSchema($refEnum->getBackingType()->getName());
        } else {
            /** @var class-string<\UnitEnum> $enumFQCN */
            $data = [];

            foreach ($enumFQCN::cases() as $case) {
                $data[$case->name] = $case->name;
            }

            $jsonType = T::STRING->value;
        }

        return [
            T::TYPE => $jsonType,
            self::ENUM => [
                self::ENUM_NAME => $refEnum->getShortName(),
                self::METHOD_VALUES => $data,
            ],
            self::ENUM_KEY => array_values($data),
        ];
    }


    public static function findEnumNameInJsonSchema(array $type): ?string
    {
        return $type[EnumResolver::ENUM][EnumResolver::ENUM_NAME] ?? null;
    }

    public static function schemaHasEnum(array $schema): bool
    {
        $hasEnum = false;

        T::filterSchema($schema, function(array $schemaObj) use (&$hasEnum) {
            if ($hasEnum || (bool)(
                    $schemaObj[T::ITEMS][self::ENUM]
                    ?? $schemaObj[T::ITEMS][self::ENUM_KEY]
                    ?? $schemaObj[self::ENUM]
                    ?? $schemaObj[self::ENUM_KEY]
                    ?? false
                )) $hasEnum = true;
        });

        return $hasEnum;
    }

    public static function enumDataFromSchema(array $schema, ?string $paramName = null, int $resultKey = 0): EnumVO
    {
        $enums = self::enumsDataFromSchema($schema, $paramName);
        return $enums[$resultKey] ?? throw new NotSupportDTOException('Enum not found in schema');
    }

    /**
     * @return EnumVO[]
     */
    public static function enumsDataFromSchema(array $schema, ?string $paramName = null): array
    {
        $enums = [];
        TypeHintResolver::filterSchema($schema, function(array $schemaObj) use (&$enums, $paramName) {
            if (($schemaObj[EnumResolver::ENUM] ?? false)
                || ($schemaObj[EnumResolver::ENUM_KEY] ?? false)
            ) {
                $enumVo = EnumVO::fromSchema($schemaObj, $paramName);
                $enums[$enumVo->name] = $enumVo;
            }
        });

        return array_values($enums);
    }


    public static function applyEnumFqcnToJsonSchema(
        string $enumFQCN,
        array $jsonSchema,
        string $methodGetValues = self::METHOD_VALUES,
        string $methodTryFrom = self::METHOD_TRY_FROM_VALUE,
    ): array
    {
        $enumSchema = self::generateEnumSchema($enumFQCN, $methodGetValues, $methodTryFrom);
        return T::applyToSchema($jsonSchema, function(array $schema) use ($enumSchema) {
            if (($schema[T::TYPE] ?? '') === ($enumSchema[T::TYPE] ?? null)) {
                $schema = [
                    ...$schema,
                    ...$enumSchema,
                ];
            }

            return $schema;
        });
    }

    public static function getEnumFQCN(string|array $type): ?string
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
