<?php

namespace Ufo\DTO\Helpers;

use InvalidArgumentException;
use ReflectionException;
use Ufo\DTO\VO\EnumVO;

use function call_user_func;

enum EnumResolver:string
{
    case STRING = TypeHintResolver::STRING->value;
    case INT = TypeHintResolver::INT->value;

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
            TypeHintResolver::TYPE => TypeHintResolver::phpToJsonSchema($refEnum->getBackingType()->getName()),
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

    public static function enumDataFromSchema(array $schema, ?string $paramName = null): EnumVO
    {
        if ($schema[TypeHintResolver::ONE_OFF] ?? false) {
            foreach ($schema[TypeHintResolver::ONE_OFF] as $type) {
                if (($type[EnumResolver::ENUM] ?? false) || ($type[EnumResolver::ENUM_KEY] ?? false)) {
                    return self::enumDataFromSchema($type, $paramName);
                }
            }
        } elseif (($schema[TypeHintResolver::ITEMS][EnumResolver::ENUM] ?? false) || ($schema[TypeHintResolver::ITEMS][EnumResolver::ENUM_KEY] ?? false)) {
            return self::enumDataFromSchema($schema[TypeHintResolver::ITEMS], $paramName);
        }
        return EnumVO::fromSchema($schema, $paramName);
    }
}
