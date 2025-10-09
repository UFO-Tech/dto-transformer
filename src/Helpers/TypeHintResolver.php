<?php

namespace Ufo\DTO\Helpers;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionException;

use Ufo\DTO\DTOTransformer;
use function array_map;
use function class_exists;
use function dirname;
use function end;
use function enum_exists;
use function implode;
use function in_array;
use function is_array;
use function is_null;
use function iterator_to_array;
use function ltrim;
use function method_exists;
use function sprintf;
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
    const string REF = '$ref';
    const string ADDITIONAL_PROPERTIES = 'additionalProperties';
    const string CLASS_FQCN = 'classFQCN';

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
            && class_exists($value);
    }

    public static function isEnum(string $value): bool
    {
        return TypeHintResolver::normalize($value) === TypeHintResolver::OBJECT->value
            && enum_exists($value);
    }

    public static function jsonSchemaToPhp(array|string $type, array $namespaces = []): string
    {
        if (is_array($type)) {
            if (!isset($type[self::TYPE]) && !isset($type[self::ONE_OFF]) && !isset($type[self::REF])) {
                throw new \InvalidArgumentException('Invalid schema: missing "type" or "oneOf" key');
            }
            if ($type[self::ONE_OFF] ?? false) {
                $types = array_map(fn($t) => TypeHintResolver::jsonSchemaToPhp($t, $namespaces), $type[self::ONE_OFF]);

                if (count($types) === 2 && in_array(self::NULL->value, $types, true)) {
                    $type = '?' . current(array_filter($types, fn($t) => $t !== self::NULL->value));
                } else {
                    $type = implode('|', $types);
                }

            } elseif (!isset($type[self::CLASS_FQCN]) && ($type[self::TYPE] ?? '') === self::OBJECT->value && isset($type[self::ADDITIONAL_PROPERTIES])) {
                $type = self::ARRAY->value;
            } elseif ($enumName = EnumResolver::findEnumNameInJsonSchema($type)) {
                $type = self::typeWithNamespace($enumName, $namespaces, 'enum');
            } else {
                $type = TypeHintResolver::jsonSchemaToPhp($type[self::TYPE] ?? $type[self::REF], $namespaces);
            }
        }

        if (str_starts_with($type,'#')) {
            $parts = explode('/', $type);
            $type = self::typeWithNamespace(end($parts), $namespaces, 'dto');
        }

        return match ($type) {
            self::NUMBER->value => self::FLOAT->value,
            self::INTEGER->value => self::INT->value,
            self::BOOLEAN->value => self::BOOL->value,
            default => $type
        };
    }


    public static function checkMixedInSchema(array $schema): bool
    {
        if (!isset($schema[self::ONE_OFF])) {
            return false;
        }
        $requiredTypes = [
            self::STRING->value,
            self::INTEGER->value,
            self::NUMBER->value,
            self::BOOLEAN->value,
            self::ARRAY->value,
            self::NULL->value,
        ];
        $schemaTypes = array_map(fn($type) => $type[self::TYPE] ?? self::MIXED->value, $schema[self::ONE_OFF] ?? []);
        sort($requiredTypes);
        sort($schemaTypes);

        return $requiredTypes === $schemaTypes;
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

    public static function typeDescriptionToJsonSchema(string $typeExpression, array $classes = []): array
    {
        $resolver = new TypeResolver();

        try {
            $type = $resolver->resolve($typeExpression);
        } catch (\Throwable) {
            return [self::TYPE => self::ANY->value];
        }

        return self::typeToSchema($type, $classes);
    }

    /**
     * @throws ReflectionException
     */
    private static function typeToSchema(Type $type, array $classes = []): array
    {
        $isObject = $type instanceof Types\Object_;
        if ($type instanceof Types\Array_) {
            if (str_contains((string)$type->getKeyType(), self::INT->value)) {
                return [
                    self::TYPE => self::ARRAY->value,
                    self::ITEMS => self::typeToSchema($type->getValueType(), $classes)
                ];
            }
            $isObject = true;
        }

        if ($isObject) {
            $fqsen = method_exists($type, 'getFqsen') ? $type->getFqsen() : null;
            $t = ltrim($type, '\\');
            $fqcn = !is_null($fqsen) ? (self::typeWithNamespaceOrDefault($t, $classes, DTOTransformer::DTO_NS_KEY)) : null;
            if (!$fqcn && class_exists($t)) {
                $fqcn = $t;
            }
            $fqcn = ($fqcn ? [self::CLASS_FQCN => $fqcn] : []);


            if (enum_exists($fqcnS = $fqcn[self::CLASS_FQCN] ?? '')) {
                return EnumResolver::generateEnumSchema($fqcnS);
            }

            return [
                ...[self::TYPE => self::OBJECT->value],
                ...[self::ADDITIONAL_PROPERTIES =>
                    method_exists($type, 'getFqsen') ?: null
                        ?? (method_exists($type, 'getValueType') ? self::typeToSchema($type->getValueType(), $classes) : true)
                ],
                ...$fqcn,
            ];
        }

        if ($type instanceof Types\Compound) {
            return [
                self::ONE_OFF => array_map(fn(Type $t) => self::typeToSchema($t, $classes), iterator_to_array($type))
            ];
        }

        if ($type instanceof Types\Nullable) {
            return [
                self::ONE_OFF => [
                    self::typeToSchema($type->getActualType(), $classes),
                    self::typeToSchema(new Types\Null_()),
                ]
            ];
        }

        if ($type instanceof Types\Mixed_) {
            return [self::ONE_OFF => self::mixedForJsonSchema()];
        }

        $phpType = self::normalize((string) $type);
        return [self::TYPE => self::phpToJsonSchema($phpType)];
    }

    public static function getUsesNamespaces(string $classFQCN): array
    {
        $classes = [];
        if (class_exists($classFQCN)) {
            $reflection = new \ReflectionClass($classFQCN);
            $contextFactory = new ContextFactory();
            $context = $contextFactory->createFromReflector($reflection);
            $classes = $context->getNamespaceAliases();

            $namespace = $reflection->getNamespaceName();

            if (!empty($namespace)) {
                $classes[DTOTransformer::DTO_NS_KEY] = $namespace;
            }
        }

        return $classes;
    }

    public static function jsonSchemaToTypeDescription(array $schema, array $namespaces = []): string
    {
        if (isset($schema[self::ONE_OFF])) {

            if (self::checkMixedInSchema($schema)) {
                return self::MIXED->value;
            }
            $types = array_map(fn($type) => self::jsonSchemaToTypeDescription($type, $namespaces), $schema[self::ONE_OFF]);

            if (count($types) === 2 && in_array(self::NULL->value, $types, true)) {
                return '?' . current(array_filter($types, fn($type) => $type !== self::NULL->value));
            } else {
                return implode('|', $types);
            }
        }
        if (!isset($schema[self::TYPE]) && !isset($schema[self::REF])) {
            return self::MIXED->value;
        }
        $type = self::jsonSchemaToPhp($schema, $namespaces);

        if (($type === self::OBJECT->value || $type === self::ARRAY->value) && is_array($schema[self::ADDITIONAL_PROPERTIES] ?? null)) {
            $valueType = self::jsonSchemaToTypeDescription($schema[self::ADDITIONAL_PROPERTIES], $namespaces);
            return sprintf('array<string,%s>', $valueType);
        }

        if ($type === self::ARRAY->value && isset($schema[self::ITEMS])) {
            $valueType = self::jsonSchemaToTypeDescription($schema[self::ITEMS], $namespaces);
            if (
                self::tryFrom($valueType)
                || class_exists($valueType)
                || (!str_contains($valueType, '|')
                    && str_ends_with($valueType, '[]'))
            ) {
                return sprintf("%s[]", $valueType);
            } else {
                return sprintf("%s<%s>", self::ARRAY->value, $valueType);
            }
        }
        if ($type === self::OBJECT->value && isset($schema[self::CLASS_FQCN])) {
            return $schema[self::CLASS_FQCN];
        }

        return $type;
    }

    protected static function typeWithNamespace(string $type, array $namespaces = [], ?string $defaultKey = null): string
    {
        $namespace = $namespaces[$type] ?? $namespaces[$defaultKey] ?? null;
        return !empty($namespace) ? $namespace . '\\' . $type : $type;
    }

    public static function typeWithNamespaceOrDefault(string $type, array $namespaces = [], ?string $defaultKey = null): ?string
    {
        $class = $namespaces[$type] ?? null;

        if (!$class) {
            $namespace = $namespaces[$defaultKey] ?? null;
            $class = !empty($namespace) ? $namespace . '\\' . $type : $type;
            if (!class_exists($class)) $class = null;
        }
        return $class;
    }

    /**
     * @param array $schema
     * @param callable(array):array $call
     * @return array
     */
    public static function applyToSchema(array $schema, callable $call, array $parentShema = []): array
    {
        if ($schema[self::ONE_OFF] ?? false) {
            foreach ($schema[self::ONE_OFF] as $i => $desc) {
                $schema[self::ONE_OFF][$i] = self::applyToSchema($desc, $call, $schema);
            }
        } elseif ($schema[self::ITEMS] ?? false) {
            $schema[self::ITEMS] = self::applyToSchema($schema[self::ITEMS], $call, $schema);
        } elseif (($schema[self::ADDITIONAL_PROPERTIES] ?? false) && is_array($schema[self::ADDITIONAL_PROPERTIES])) {
            $schema[self::ADDITIONAL_PROPERTIES] = self::applyToSchema($schema[self::ADDITIONAL_PROPERTIES], $call, $schema);
        }

        return $call($schema, $parentShema);
    }

    /**
     * @param array $schema
     * @param callable(array, array):void $call
     * @param array $parentShema
     */
    public static function filterSchema(array $schema, callable $call, array $parentShema = []): void
    {
        if ($schema[self::ONE_OFF] ?? false) {
            foreach ($schema[self::ONE_OFF] as $desc) {
                self::filterSchema($desc, $call, $schema);
            }
        } elseif ($schema[self::ITEMS] ?? false) {
            self::filterSchema($schema[self::ITEMS], $call, $schema);
        } elseif (($schema[self::ADDITIONAL_PROPERTIES] ?? false) && is_array($schema[self::ADDITIONAL_PROPERTIES])) {
            self::filterSchema($schema[self::ADDITIONAL_PROPERTIES], $call, $schema);
        }

        $call($schema, $parentShema);
    }

}
