<?php

namespace Ufo\DTO\Helpers;

use InvalidArgumentException;
use ReflectionException;

class EnumsHelper
{
    const string METHOD_VALUES = 'values';
    const string METHOD_FROM_VALUE = 'fromValue';
    const string METHOD_TRY_FROM_VALUE = 'tryFromValue';
    const string ENUM_KEY = 'enum';

    /**
     * @param class-string $enumFQCN
     * @throws ReflectionException|InvalidArgumentException
     */
    public static function generateEnumSchema(string $enumFQCN, string $method = self::METHOD_VALUES): array
    {
        $refEnum = new \ReflectionEnum($enumFQCN);

        $data = array_column($enumFQCN::cases(), 'value', 'name');
        if (method_exists($enumFQCN, $method) && method_exists($enumFQCN, static::METHOD_FROM_VALUE)) {
            foreach ($enumFQCN::values() as $i => $value) {
                $methodTry = static::METHOD_TRY_FROM_VALUE;
                $enum = call_user_func([$enumFQCN, $methodTry],$value) ?? throw new InvalidArgumentException('Invalid value "' . $value . '" for enum ' . $enumFQCN);
                $data[$enum->name] = $value;
            }
        }

        return [
            TypeHintResolver::TYPE => TypeHintResolver::phpToJsonSchema($refEnum->getBackingType()->getName()),
            XUfoValuesEnum::ENUM->value => [
                XUfoValuesEnum::ENUM_NAME->value => $refEnum->getShortName(),
                XUfoValuesEnum::ENUM_VALUES->value => $data,
            ],
            static::ENUM_KEY => array_values($data)
        ];
    }
}
