<?php

declare(strict_types = 1);

namespace Ufo\DTO\Transformer;

use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Component\Serializer\Attribute\Ignore;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\DTOAttributesEnum;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\DTOFromSmartArrayTransformerInterface;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\VO\TransformationContext;
use Ufo\DTO\VO\TransformKeyVO;

class DTOFromArrayTransformer implements DTOFromArrayTransformerInterface, DTOFromSmartArrayTransformerInterface
{
    /**
     * @var array<string, TransformKeyVO>
     */
    protected array $defaultPropertyKeys = [];

    public function __construct(
        protected ParamHydratorChainInterface $hydratorChain,
        protected TypeSchemaResolverInterface $typeSchemaResolver,
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected ReflectionMetadataKeyGeneratorInterface $keyGenerator,
    ) {}

    /**
     * @throws BadParamException
     * @throws ReflectionException
     */
    public function transformFromArray(
        string $classFQCN,
        array  $data,
        array  $renameKey = [],
        array  $namespaces = [],
        array  $context = [],
    ): object
    {
        $context = TransformationContext::fromArray($context, namespaces: $namespaces);
        $classFQCN = $this->resolveClassForData($classFQCN, $data, $context);
        return $this->hydrateClass($classFQCN, $data, $renameKey, $context);
    }

    public function transformFromSmartArray(
        array $data,
        array $renameKey = [],
        array $namespaces = [],
        array $context = [],
    ): object
    {
        $classFQCN = $data[BaseDTOFromArrayTransformer::DTO_CLASSNAME]
            ?? throw new NotSupportDTOException('Missing class name');

        $context = TransformationContext::fromArray($context, namespaces: $namespaces);
        $classFQCN = $this->resolveSmartClass($classFQCN, $context);
        unset($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME]);

        return $this->hydrateClass($classFQCN, $data, $renameKey, $context);
    }

    protected function hydrateClass(
        string $classFQCN,
        array $data,
        array $renameKey,
        TransformationContext $context,
    ): object
    {
        $reflectionClass = $this->reflectionClass($classFQCN);
        $declaringNamespaces = $this->metadataProvider->declaringNamespaces($reflectionClass);
        $context = $context->withNamespaces($declaringNamespaces);

        $instance = $this->createUninitializedInstance($reflectionClass);
        foreach ($this->reflectionProperties($reflectionClass) as $property) {
            $this->hydrateProperty(
                instance: $instance,
                property: $property,
                data: $data,
                refClass: $reflectionClass,
                context: $context,
                renameKey: $renameKey
            );
        }

        return $instance;
    }

    protected function resolveClassForData(
        string $classFQCN,
        array &$data,
        TransformationContext $context,
    ): string
    {
        if (!isset($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME])) {
            return $classFQCN;
        }

        try {
            $classFQCN = $this->resolveSmartClass($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME], $context);
            unset($data[BaseDTOFromArrayTransformer::DTO_CLASSNAME]);
        } catch (NotSupportDTOException) {}

        return $classFQCN;
    }

    protected function resolveSmartClass(mixed $classFQCN, TransformationContext $context): string
    {
        if (!is_string($classFQCN)) {
            throw new NotSupportDTOException('Smart DTO class name must be a string');
        }

        if (class_exists($classFQCN)) {
            return $classFQCN;
        }

        $namespace = $context->namespaces()[$classFQCN] ?? $context->namespaces()[BaseDTOFromArrayTransformer::DTO_NS_KEY]
            ?? throw new NotSupportDTOException('Namespace not found for class: ' . $classFQCN);

        $classFQCN = $namespace . '\\' . $classFQCN;
        if (!class_exists($classFQCN)) {
            throw new NotSupportDTOException('Class not exist: ' . $classFQCN);
        }
        return $classFQCN;
    }

    /**
     * @throws ReflectionException
     */
    protected function hydrateProperty(
        object $instance,
        ReflectionProperty $property,
        array $data,
        ReflectionClass $refClass,
        ?TransformationContext $context,
        array $renameKey = [],
    ): void
    {
        if ($this->metadataProvider->attributes($property, Ignore::class, ReflectionAttribute::IS_INSTANCEOF)) {
            try {
                $name = $property->getName();
                $data[$name] = $this->getPropertyValue($name, $property, $property->getDeclaringClass());
            } catch (\Throwable $e) {
                return;
            }
        }

        $keys = $this->propertyKey($property, $renameKey);
        $attr = $this
            ->metadataProvider
            ->attributes($property, AttrAssertions::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        if ($attr) {
            DTOAttributesEnum::ASSERTIONS->process(
                $attr->newInstance(),
                $data[$keys->dataKey] ?? [],
                $property,
                $this,
            );
        }

        $property->setValue($instance, $this->extractValue(
            key: $keys->dataKey,
            data: $data,
            ref: $property,
            refClass: $refClass,
            context: $context,
        ));
    }

    /**
     * @throws ReflectionException
     * @throws BadParamException
     */
    protected function extractValue(
        string                                 $key,
        array                                  $data,
        ReflectionParameter|ReflectionProperty $ref,
        ReflectionClass                        $refClass,
        TransformationContext                  $context,
    ): mixed
    {
        if (array_key_exists($key, $data)) {
            $reflection = $ref instanceof ReflectionProperty && $ref->isPromoted()
                ? $this->getPromotedConstructorParameter($ref, $refClass) ?? $ref
                : $ref;

            return $this->resolveValue($reflection, $data[$key], $context);
        }
        return $this->getPropertyValue($key, $ref, $refClass);
    }

    /**
     * @throws BadParamException
     */
    protected function resolveValue(
        ReflectionProperty|ReflectionParameter $reflection,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        $attributeDefinition = $this
            ->metadataProvider
            ->attributes($reflection, AttrDTO::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        if ($attributeDefinition) {
            return DTOAttributesEnum::tryFromAttr(
                $attributeDefinition,
                $value,
                $reflection,
                $this,
                $context,
            );
        }

        $resolvedSchema = $this->typeSchemaResolver->schema($reflection, $context->namespaces());
        $schema = $resolvedSchema->schema;

        if (!$schema) {
            return $value;
        }

        try {
            return $this->hydratorChain->resolve($schema, $value, $context->withStrict($resolvedSchema->strict));
        } catch (\Throwable $e) {
            if (!$resolvedSchema->strict) {
                return $value;
            }

            throw new BadParamException(
                sprintf('Failed to resolve %s: %s', $this->reflectionName($reflection), $e->getMessage()),
                $e->getCode(),
                $e,
            );
        }
    }

    protected function reflectionName(ReflectionProperty|ReflectionParameter $reflection): string
    {
        return $reflection instanceof ReflectionProperty
            ? sprintf('%s::$%s', $reflection->getDeclaringClass()->getName(), $reflection->getName())
            : sprintf('%s() parameter $%s', $reflection->getDeclaringFunction()->getName(), $reflection->getName());
    }

    protected function getPropertyValue(
        string                                 $key,
        ReflectionParameter|ReflectionProperty $ref,
        ReflectionClass                        $refClass
    )
    {
        return match (true) {
            $ref instanceof ReflectionParameter => $ref->isOptional()
                ? $ref->getDefaultValue()
                : throw new InvalidArgumentException("Missing required key for constructor param: '$key'"),

            $ref instanceof ReflectionProperty => (function () use ($refClass, $ref, $key) {
                $instance = $this->createUninitializedInstance($refClass);
                try {
                    return $ref->getValue($instance);
                } catch (\Throwable) {
                    if (!$ref->isInitialized($instance)) {
                        $constructor = $refClass->getConstructor();

                        if ($constructor !== null) {
                            $init = false;
                            $value = null;
                            foreach ($constructor->getParameters() as $p) {
                                if ($init = $p->getName() === $key && $p->isOptional()) {
                                    $value = $p->getDefaultValue();
                                    break;
                                }
                            }
                            if ($init && $this->canAssignToParameter($value, $ref)) return $value;

                        }
                    }
                    throw new InvalidArgumentException("Missing required key for property: '$key'");
                }
            })(),

            default => throw new InvalidArgumentException('Unsupported reflection type'),
        };
    }

    protected function canAssignToParameter(mixed $value, ReflectionParameter|ReflectionProperty $parameter): bool
    {
        $type = $parameter->getType();

        if ($type === null) return true;
        if ($value === null) return $type->allowsNull();

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if (TypeHintResolver::tryFrom($innerType->getName())->matchType($value) ?? false) return true;
            }
            return false;
        }

        return TypeHintResolver::tryFrom($type->getName())->matchType($value) ?? false;
    }

    protected function getPromotedConstructorParameter(
        ReflectionProperty $property,
        ReflectionClass $refClass,
    ): ?ReflectionParameter
    {
        return $this->metadataProvider->promotedConstructorParameter($property, $refClass)?->reflection;
    }

    protected static function getPropertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): TransformKeyVO
    {
        $dtoKey = $property->getName();
        $dataKey = array_key_exists($dtoKey, $renameKey) ? $renameKey[$dtoKey] : $dtoKey;
//        if ($property->getAttributes(Ignore::class)[0] ?? null) {
//            $dataKey = null;
//        }
        return new TransformKeyVO($dtoKey, $dataKey);
    }

    protected function propertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): TransformKeyVO
    {
        if ($renameKey !== []) {
            return static::getPropertyKey($property, $renameKey);
        }

        $key = $property instanceof ReflectionProperty
            ? $this->keyGenerator->propertyKey($property)
            : $this->keyGenerator->parameterKey($property);

        return $this->defaultPropertyKeys[$key] ??= new TransformKeyVO($property->getName(), $property->getName());
    }

    /**
     * @throws ReflectionException
     */
    protected function createUninitializedInstance(string|ReflectionClass $class): object
    {
        $reflection = $class instanceof ReflectionClass ? $class : $this->reflectionClass($class);
        return $reflection->newInstanceWithoutConstructor();
    }

    protected function reflectionClass(string $classFQCN): ReflectionClass
    {
        return $this->metadataProvider->classMetadata($classFQCN)->reflection;
    }

    /**
     * @return ReflectionProperty[]
     */
    protected function reflectionProperties(ReflectionClass $reflection): array
    {
        return $this->metadataProvider->reflectionProperties($reflection);
    }

    public function support(string $classFQCN): bool
    {
        return class_exists($classFQCN);
    }
}
