<?php

namespace Ufo\DTO\Helpers;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types;

use function array_map;
use function class_exists;
use function enum_exists;
use function implode;
use function is_array;
use function iterator_to_array;
use function method_exists;
use function str_contains;
use function strtolower;

enum TypeHintResolver: string
{
    case STRING = 'string';
    case STR = 'str';
    case ARR = 'arr';
    case ARRAY = 'array';
    case COLLECTION = 'collection';
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
    const string ITEMS = 'items';
    const string ONE_OFF = 'oneOf';

    public static function normalize(string $type): string
    {
        return match (strtolower($type)) {
            self::ANY->value, self::MIXED->value => '',
            self::COLLECTION->value, self::ARR->value, self::ARRAY->value => self::ARRAY->value,
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
        return TypeHintResolver::normalize($value) === TypeHintResolver::OBJECT->value
               && !enum_exists($value)
               && class_exists($value)
            ;
    }

   public static function isEnum(string $value): bool
    {
        return TypeHintResolver::normalize($value) === TypeHintResolver::OBJECT->value
               && enum_exists($value)
            ;
    }

    public static function jsonSchemaToPhp(array|string $type): string
    {
        if (is_array($type)) {
            if (!isset($type[self::TYPE]) && !isset($type[self::ONE_OFF])) {
                throw new \InvalidArgumentException('Invalid schema: missing "type" or "oneOf" key');
            }
            if ($type[self::ONE_OFF] ?? false) {
                $types = array_map(fn($t) => TypeHintResolver::jsonSchemaToPhp($t[self::TYPE]), $type[self::ONE_OFF]);
                $type = implode('|', $types);
            } else {
                $type = TypeHintResolver::jsonSchemaToPhp($type[self::TYPE]);
            }
        }
        return match ($type) {
            self::NUMBER->value => self::FLOAT->value,
            self::INTEGER->value => self::INT->value,
            self::BOOLEAN->value => self::BOOL->value,
            default => $type
        };
    }

    public static function phpToJsonSchema(string $phpType): string
    {
        return match ($phpType) {
            self::MIXED->value => '',
            self::FLOAT->value => self::NUMBER->value,
            self::INT->value => self::INTEGER->value,
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

    public static function typeDescriptionToJsonSchema(string $typeExpression): array
    {
        $resolver = new TypeResolver();

        try {
            $type = $resolver->resolve($typeExpression);
        } catch (\Throwable) {
            return [self::TYPE => self::ANY->value];
        }

        return self::typeToSchema($type);
    }

    private static function typeToSchema(Type $type): array
    {
        $isObject = $type instanceof Types\Object_;
        if ($type instanceof Types\Array_) {
            if (str_contains((string)$type->getKeyType(), self::INT->value)) {
                return [
                    self::TYPE => self::ARRAY->value,
                    self::ITEMS => self::typeToSchema($type->getValueType())
                ];
            }
            $isObject = true;
        }

        if ($isObject) {
            return [
                self::TYPE => self::OBJECT->value,
                'additionalProperties' =>
                    method_exists($type, 'getFqsen') ?: null
                    ?? (method_exists($type, 'getValueType') ? self::typeToSchema($type->getValueType()) : true)
            ];
        }

        if ($type instanceof Types\Compound) {
            return [
                self::ONE_OFF => array_map(fn(Type $t) => self::typeToSchema($t), iterator_to_array($type))
            ];
        }

        if ($type instanceof Types\Nullable) {
            return [
                self::ONE_OFF => [
                    self::typeToSchema($type->getActualType()),
                    self::typeToSchema(new Types\Null_()),
                ]
            ];
        }

        $phpType = self::normalize((string) $type);
        return [self::TYPE => self::phpToJsonSchema($phpType)];
    }
}
