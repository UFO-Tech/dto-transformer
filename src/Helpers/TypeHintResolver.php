<?php

namespace Ufo\DTO\Helpers;

use function array_map;
use function class_exists;
use function implode;
use function is_array;
use function strtolower;

enum TypeHintResolver: string
{
    case STRING = 'string';
    case STR = 'str';
    case ARR = 'arr';
    case ARRAY = 'array';
    case NULL = 'null';
    case NIL = 'nil';
    case OBJECT = 'object';
    case MIXED = 'mixed';
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case ANY = 'any';
    case INTEGER = 'integer';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case VOID = 'void';
    case TRUE = 'true';
    case FALSE = 'false';
    case DBL = 'dbl';
    case DOUBLE = 'double';
    const string TYPE = 'type';

    public static function normalize(string $type): string
    {
        return match (strtolower($type)) {
            self::ANY->value, self::MIXED->value => '',
            self::ARR->value, self::ARRAY->value => self::ARRAY->value,
            self::BOOL->value, self::TRUE->value, self::BOOLEAN->value, self::FALSE->value => self::BOOL->value,
            self::DBL->value, self::DOUBLE->value, self::FLOAT->value => self::FLOAT->value,
            self::INTEGER->value, self::INT->value => self::INT->value,
            self::NIL->value, self::NULL->value, self::VOID->value => self::NULL->value,
            self::STRING->value, self::STR->value => self::STRING->value,
            default => self::OBJECT->value
        };
    }

    public static function normalizeArray(array $types): array
    {
        return array_map(fn (string $type): string => self::normalize($type), $types);
    }

    public static function isRealClass(string $value): bool
    {
        return TypeHintResolver::normalize($value) === TypeHintResolver::OBJECT->value && class_exists($value);
    }

    public static function jsonSchemaToPhp(array|string $type): string
    {
        if (is_array($type)) {
            if (!isset($type['type']) && !isset($type['oneOf'])) {
                throw new \InvalidArgumentException('Invalid schema: missing "type" or "oneOf" key');
            }
            if ($type['oneOf'] ?? false) {
                $types = array_map(fn($t) => TypeHintResolver::jsonSchemaToPhp($t['type']), $type['oneOf']);
                $type = implode('|', $types);
            } else {
                $type = TypeHintResolver::jsonSchemaToPhp($type['type']);
            }
        }
        return match ($type) {
            self::NUMBER->value => self::FLOAT->value,
            self::INTEGER->value => self::INT->value,
            self::BOOLEAN->value => self::BOOL->value,
            default => $type
        };
    }

    public static function phpToJsonSchema(array|string $phpType): string
    {
        return match ($phpType) {
            self::MIXED->value => '',
            self::FLOAT->value => self::NUMBER->value,
            self::INT->value, => self::INTEGER->value,
            self::BOOL->value => self::BOOLEAN->value,
            default => $phpType
        };
    }

    public static function mixedForJsonSchema(): array
    {
        return [
            [self::TYPE => self::STRING->value],
            [self::TYPE => self::INTEGER->value],
            [self::TYPE => self::NUMBER->value],
            [self::TYPE => self::BOOLEAN->value],
            [self::TYPE => self::ARRAY->value],
            [self::TYPE => self::NULL->value],
        ];
    }

}
