<?php

namespace Ufo\DTO;

use BackedEnum;
use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Throwable;
use TypeError;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Interfaces\IArrayConvertible;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\IDTOToArrayTransformer;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\VO\TransformKeyVO;
use Symfony\Component\Serializer\Attribute\Ignore;

use UnitEnum;
use function array_key_exists;
use function array_map;
use function gettype;
use function strcasecmp;

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
    public static function toArray(object $dto, array $renameKey = [], bool $asSmartArray = true): array
    {
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties();
        $array = [];

        foreach ($properties as $property) {
            $keys = static::getPropertyKey($property, $renameKey);
            if (!$keys->dataKey) continue;
            $value = $property->getValue($dto);
            $value = static::convertValue($value, $asSmartArray);
            $array[$keys->dataKey] = $value;
        }

        if ($asSmartArray) {
            $array[static::DTO_CLASSNAME] = $reflection->getShortName();
        }

        return $array;
    }

    protected static function convertValue(mixed $value, bool $asSmartArray): mixed
    {
        return match (gettype($value)) {
            TypeHintResolver::ARRAY->value => static::mapArrayWithKeys($value, $asSmartArray),
            TypeHintResolver::OBJECT->value => (function () use ($value, $asSmartArray) {
                if ($value instanceof UnitEnum) {
                    return $value instanceof BackedEnum ? $value->value : $value->name;
                }
                return $value instanceof IArrayConvertible
                    ? $value->toArray()
                    : static::toArray($value, asSmartArray: $asSmartArray);
            })(),
            default => $value,
        };
    }

    protected static function mapArrayWithKeys(array $array, bool $asSmartArray): array
    {
        return array_map(fn($v) => static::convertValue($v, $asSmartArray), $array);
    }

    /**
     * @throws BadParamException
     * @throws ReflectionException
     */
    protected static function transformFromArray(
        string $classFQCN,
        array $data,
        array $renameKey = [],
        array $namespaces = []
    ): object
    {
        $instance = null;
        $reflectionClass = new ReflectionClass($classFQCN);
        $constructParams = [];
        $hasReadonly = static::checkReadonlyInClass($reflectionClass);

        $paramsDocTypes = [];
        $constructor = $reflectionClass->getConstructor();
        if ($constructor) {
            $paramsDocTypes = static::getConstructorDocTypes($constructor);
        }
        $classes = static::getClassUses($reflectionClass);

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $param) {
                static::processParamResolve(
                    $param,
                    $data,
                    function (
                        TransformKeyVO      $keys,
                        mixed               $data,
                        ReflectionParameter $param,
                    ) use (&$constructParams, $reflectionClass, $namespaces, $classes, $paramsDocTypes) {
                        $constructParams[] = static::extractValue(
                            $keys->dataKey, $data, $param, $reflectionClass, namespaces: $namespaces, classes: $classes, paramsDocTypes: $paramsDocTypes
                        );
                    },
                    $renameKey,
                    $hasReadonly
                );
            }
            $instance = $reflectionClass->newInstanceArgs($constructParams);
        }
        $instance = $instance ?? static::createUninitializedInstance($reflectionClass);

        foreach ($reflectionClass->getProperties() as $property) {
            if (isset($constructParams[$property->getName()])) continue;

            static::processParamResolve(
                $property,
                $data,
                fn (
                    TransformKeyVO $keys,
                    mixed $data,
                    ReflectionProperty $param,
                ) => $property->setValue($instance, static::extractValue(
                    $keys->dataKey, $data, $param, $reflectionClass, namespaces: $namespaces, classes: $classes, paramsDocTypes: $paramsDocTypes
                )),
                $renameKey,
                $hasReadonly,
                $constructParams
            );
        }

        return $instance;
    }


    protected static function processParamResolve(
        ReflectionParameter|ReflectionProperty $param,
        array $data,
        callable $process,
        array $renameKey = [],
        bool $hasReadonly = false,
        array $ignoreParams = []
    ): void
    {
        $keys = static::getPropertyKey($param, $renameKey);

        $skip = match (true) {
            $param instanceof ReflectionParameter => !$keys->dataKey,
            $param instanceof ReflectionProperty => !$keys->dataKey
                || $param->isReadOnly()
                || ($hasReadonly && array_key_exists($keys->dtoKey, $ignoreParams))
        };

        if ($skip) return;

        $attr = $param->getAttributes(AttrAssertions::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if ($attr) {
            DTOAttributesEnum::ASSERTIONS->process(
                $attr->newInstance(), $data, $param, static::class
            );
        }

        $process($keys, $data, $param);
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
        return class_exists($classFQCN);
    }

    /**
     * @throws ReflectionException
     * @throws BadParamException
     */
    protected static function extractValue(
        string $key,
        array $data,
        ReflectionParameter|ReflectionProperty $ref,
        ReflectionClass $refClass,
        array $namespaces = [],
        array $classes = [],
        array $paramsDocTypes = []
    ): mixed {
        if (array_key_exists($key, $data)) {
            return static::checkAttributes(
                $ref, $data[$key], namespaces: $namespaces, classes: $classes, paramsDocTypes: $paramsDocTypes
            );
        }

        return match (true) {
            $ref instanceof ReflectionParameter => $ref->isOptional()
                ? $ref->getDefaultValue()
                : throw new InvalidArgumentException("Missing required key for constructor param: '$key'"),

            $ref instanceof ReflectionProperty => (function () use ($refClass, $ref, $key) {
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

    protected static function getClassUses(ReflectionClass $refClass): array
    {
        $context = (new ContextFactory)->createFromReflector($refClass);
        return [
            ...$context->getNamespaceAliases(),
            ...[static::DTO_NS_KEY => $context->getNamespace()]
        ];
    }

    /**
     * @throws BadParamException
     */
    protected static function checkAttributes(
        ReflectionProperty|ReflectionParameter $property,
        mixed $value,
        array $namespaces = [],
        array $classes = [],
        array $paramsDocTypes = [],
    ): mixed
    {
        $dtoAttributes = [];
        try {
            if (str_contains(TypeHintResolver::ARRAY->value, (string) $property->getType())) {
                $dtoAttributes = static::getDockType($property, $classes, $paramsDocTypes,true);
            }
        } catch (TypeError $e) {}

        $attributeDefinition = $property->getAttributes(AttrDTO::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        if ($attributeDefinition) {
            $value = DTOAttributesEnum::tryFromAttr($attributeDefinition, $value, $property, static::class);
        } elseif (!empty($dtoAttributes) && is_array($value)) {

            $result = [];
            foreach ($dtoAttributes as $dto) {
                /**
                 * @var AttrDTO $dto
                 */
                if ($dto->isCollection()) {
                    foreach ($value as $key => $val) {
                        if (is_object($result[$key] ?? null)) continue;
                        try {
                            $result[$key] = DTOAttributesEnum::DTO->process(
                                new AttrDTO($dto->dtoFQCN, context: [
                                    ...$dto->context,
                                    AttrDTO::C_COLLECTION => false,
                                ]), $val, $property, static::class
                            );
                        } catch (Throwable $e) {
                            $result[$key] = $val;
                        }
                    }
                } else {
                    $result = DTOAttributesEnum::DTO->process($dto, $value, $property, static::class);
                }

            }
            if ($result) {
                $value = $result;
            }
        } elseif (is_array($value)) {
            try {
                return static::fromSmartArray($value, namespaces: [
                    ...$namespaces,
                    ...static::getClassesFromType($property->getType())
                ]);
            } catch (NotSupportDTOException|ReflectionException) {
                return static::resolveValueForType($property->getType(), $value, $property);
            }
        } elseif (is_string($value) || is_int($value)) {
            try {
                $value = static::checkEnum($property->getType(), $value, $property);
            } catch (\Throwable) {}
        }

        return $value;
    }

    protected static function getDockType(
        ReflectionProperty $property,
        array $namespaces = [],
        array $paramsDocTypes = [],
        bool $isCollection = false
    ): array
    {
        $docBlockText = $property->getDocComment() ?: ' ';
        $docBlock = DocBlockFactory::createInstance()->create($docBlockText);
        $docType = (string) ($docBlock->getTags('var')[0] ?? $paramsDocTypes[$property->getName()] ?? ' ');
        $docType = (empty($docType)) ? $property->getType() : $docType;

        $jsonSchema = TypeHintResolver::typeDescriptionToJsonSchema($docType, $namespaces);

//        $bool = EnumResolver::schemaHasEnum($jsonSchema);
        $context = [
            AttrDTO::C_COLLECTION => $isCollection,
            AttrDTO::C_NS => $namespaces,
            AttrDTO::C_PROPERTY => $property
        ];

        $attributes = [];
        TypeHintResolver::filterSchema(
            $jsonSchema,
            function(array $item) use (&$attributes, $context, $namespaces) {
                $classFQCN = null;

                if ($enumName = $item[EnumResolver::ENUM][EnumResolver::ENUM_NAME] ?? false) {
                    $classFQCN = TypeHintResolver::typeWithNamespaceOrDefault($enumName, $namespaces, static::DTO_NS_KEY);
                    $context[AttrDTO::C_IS_ENUM] = true;
                } elseif ($item['classFQCN'] ?? false) {
                    $classFQCN = $item['classFQCN'];
                }

                if ($classFQCN) {
                    $attributes[] = new AttrDTO(
                        $classFQCN,
                        context: $context
                    );
                }
            }
        );
        return $attributes;
    }

    protected static function getConstructorDocTypes(ReflectionMethod $constructor): array
    {
        $docBlockText = $constructor->getDocComment() ?: '';
        if (empty($docBlockText)) return [];

        $docBlock = DocBlockFactory::createInstance()->create($docBlockText);
        $params   = [];

        foreach ($docBlock->getTagsByName('param') as $tag) {
            /** @var Param $tag */
            $type = (string) $tag->getType();

            $paramName = $tag->getVariableName();
            if (is_null($paramName)) continue;
            $params[ltrim($paramName, '$')] = $type;
        }

        return $params;
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

    /**
     * @throws ReflectionException
     */
    protected static function getClassesFromType(ReflectionType $type): array
    {
        $classes = [];

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                $classes = [
                    ...$classes,
                    ...static::getClassesFromType($subType)
                ];
            }
        }

        if ($type instanceof ReflectionNamedType && TypeHintResolver::isRealClass($type->getName())) {
            $refClass = new ReflectionClass($type->getName());
            $classes[$refClass->getShortName()] = $refClass->getNamespaceName();
        }

        return $classes;
    }

    protected static function checkEnum(
        \ReflectionType $type,
        int|string $value,
        ReflectionParameter|ReflectionProperty $ref,
    ): ?UnitEnum
    {
        if ($type instanceof ReflectionUnionType) {
            foreach ($ref->getType()->getTypes() as $t) {
                try {
                    return static::checkEnum($t, $value, $ref);
                } catch (Throwable) {}
            }
        }

        if (enum_exists($enumFQCN = $ref->getType()->getName())) {
            return static::transformEnum($enumFQCN, $value);
        }

        throw new BadParamException(sprintf(
            "Cannot assign value %s to property %s::\$%s of type %s",
            $value,
            $type->getDeclaringClass()->getName(),
            $type->getName(),
            $type->getType()
        ));
    }

    /** @param class-string<UnitEnum|BackedEnum> $enumFQCN */
    public static function transformEnum(
        string $enumFQCN,
        string|int $value,
    ): UnitEnum|string|int
    {
        if (is_subclass_of($enumFQCN, BackedEnum::class)) {
            return $enumFQCN::tryFrom($value)
                ?? throw new BadParamException(
                    sprintf(
                        'Invalid value "%s" for enum %s',
                        $value,
                        $enumFQCN
                    )
                );
        }

        foreach ($enumFQCN::cases() as $case) {
            if (strcasecmp($case->name, (string) $value) === 0) {
                return $case;
            }
        }

        return $value;
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

    public static function fromSmartArray(array $data, array $renameKey = [], array $namespaces = []): object
    {
        $className = $data[static::DTO_CLASSNAME] ?? throw new NotSupportDTOException('Missing class name');

        $namespace = $namespaces[$className] ?? $namespaces[static::DTO_NS_KEY] ?? throw new NotSupportDTOException('Namespace not found for class: ' . $className);
        $classFQCN = $namespace . '\\' . $className;
        if (!class_exists($classFQCN)) throw new NotSupportDTOException('Class not exist: ' . $classFQCN);

        unset($data[static::DTO_CLASSNAME]);
        return static::fromArray($classFQCN, $data, $renameKey, namespaces: $namespaces);
    }


}
