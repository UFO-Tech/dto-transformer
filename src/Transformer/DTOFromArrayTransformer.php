<?php

declare(strict_types = 1);

namespace Ufo\DTO\Transformer;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Schema\CompiledTypeSchema;
use Ufo\DTO\Schema\DefaultValueResolver;
use Ufo\DTO\Schema\PropertySchema;
use Ufo\DTO\Schema\Type\ResolvedTypeSchema;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;
use Ufo\DTO\Transformer\Attribute\AssertionsValidator;
use Ufo\DTO\Transformer\Attribute\AttrDTOResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProviderInterface;
use Ufo\DTO\Transformer\Hydrator\HydratorChainInterface;
use Ufo\DTO\Transformer\Hydrator\SchemaNodeHydratorInterface;

use function array_key_exists;
use function sprintf;

class DTOFromArrayTransformer implements DTOFromArrayTransformerInterface, DTOFromSmartArrayTransformerInterface, DTOFromContextTransformerInterface, DTOCandidateFilterInterface
{
    public function __construct(
        protected HydratorChainInterface $hydratorChain,
        protected TypeSchemaResolverInterface $typeSchemaResolver,
        protected ClassSchemaProviderInterface $schemaProvider,
        protected CompiledTypeSchemaProviderInterface $typeSchemaProvider,
        protected DefaultValueResolver $defaultValueResolver,
        protected SmartClassResolver $smartClassResolver,
        protected DTOCandidateFilterInterface $candidateFilter,
        protected AssertionsValidator $assertionsValidator,
        protected AttrDTOResolver $attrDTOResolver,
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
        return $this->transformFromContext(
            $classFQCN,
            $data,
            TransformationContext::fromArray($context, namespaces: $namespaces),
            $renameKey,
        );
    }

    /**
     * @throws BadParamException
     * @throws ReflectionException
     */
    public function transformFromContext(
        string $classFQCN,
        array $data,
        TransformationContext $context,
        array $renameKey = [],
    ): object
    {
        $classFQCN = $this->smartClassResolver->resolveForData($classFQCN, $data, $context);
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
        $classFQCN = $this->smartClassResolver->resolve($classFQCN, $context);
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
        $schema = $this->schemaProvider->schema($classFQCN);
        $reflectionClass = $schema->reflection;
        $context = $context->withNamespaces($schema->declaringNamespaces);

        $instance = $reflectionClass->newInstanceWithoutConstructor();

        if ($renameKey !== []) {
            $data = $this->renameDataKeys($data, $renameKey);
        }

        $typeSchemas = $this->typeSchemaProvider->typeSchemas($schema, $context->namespaces());
        foreach ($schema->properties as $index => $propertySchema) {
            $this->hydrateProperty($instance, $propertySchema, $data, $reflectionClass, $context, $typeSchemas[$index] ?? null, $renameKey);
        }

        return $instance;
    }

    /**
     * Ключі даних під іменами властивостей: значення перейменованої властивості береться з її ключа в `$renameKey`.
     *
     * @param array<string, string|null> $renameKey
     */
    protected function renameDataKeys(array $data, array $renameKey): array
    {
        $renamed = $data;
        foreach ($renameKey as $property => $dataKey) {
            unset($renamed[$property]);
            if ($dataKey !== null && array_key_exists($dataKey, $data)) {
                $renamed[$property] = $data[$dataKey];
            }
        }

        return $renamed;
    }

    /**
     * @throws ReflectionException
     */
    protected function hydrateProperty(
        object $instance,
        PropertySchema $propertySchema,
        array $data,
        ReflectionClass $refClass,
        TransformationContext $context,
        ?CompiledTypeSchema $typeSchema = null,
        array $renameKey = [],
    ): void
    {
        $property = $propertySchema->property;
        $key = $propertySchema->name;

        if ($propertySchema->ignored) {
            try {
                $data[$key] = $this->defaultValueResolver->currentValue($propertySchema, $property->getDeclaringClass(), $key);
            } catch (\Throwable) {
                return;
            }
        }

        if (!array_key_exists($key, $data)) {
            $property->setValue($instance, $this->defaultValueResolver->resolve($propertySchema, $refClass, $renameKey[$key] ?? $key));
            return;
        }

        if ($propertySchema->assertions) {
            $this->assertionsValidator->validate($propertySchema->assertions, $data[$key], $property);
        }

        $property->setValue($instance, $this->resolvePropertyValue($propertySchema, $data[$key], $context, $typeSchema));
    }

    /**
     * @throws BadParamException
     */
    protected function resolvePropertyValue(
        PropertySchema $propertySchema,
        mixed $value,
        TransformationContext $context,
        ?CompiledTypeSchema $typeSchema = null,
    ): mixed
    {
        $reflection = $propertySchema->valueReflection;

        if ($propertySchema->attrDto) {
            return $this->attrDTOResolver->resolve($propertySchema->attrDto, $value, $reflection, $this, $context);
        }

        if ($typeSchema !== null) {
            return $this->resolveWithSchema($reflection, $typeSchema->resolved, $value, $context, $typeSchema);
        }

        $resolved = $propertySchema->typeSource === null
            ? $this->typeSchemaResolver->schema($reflection, $context->namespaces())
            : $this->typeSchemaResolver->fromSource($propertySchema->typeSource, $context->namespaces());

        return $this->resolveWithSchema($reflection, $resolved, $value, $context);
    }

    /**
     * @throws BadParamException
     */
    protected function resolveWithSchema(
        ReflectionProperty|ReflectionParameter $reflection,
        ResolvedTypeSchema $resolvedSchema,
        mixed $value,
        TransformationContext $context,
        ?CompiledTypeSchema $typeSchema = null,
    ): mixed
    {
        $schema = $resolvedSchema->schema;

        if (!$schema) {
            return $value;
        }

        try {
            $context = $context->withStrict($resolvedSchema->strict);

            $node = $typeSchema?->node;
            if ($node === null) {
                return $this->hydratorChain->resolve($schema, $value, $context);
            }

            return $node->hydrator instanceof SchemaNodeHydratorInterface
                ? ($node->hydrator->resolveNode($node, $value, $context) ?? $value)
                : ($node->hydrator?->resolve($schema, $value, $context) ?? $value);
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

    public function support(string $classFQCN): bool
    {
        return class_exists($classFQCN);
    }

    public function rejectsData(string $classFQCN, array $data, array $renameKey = []): bool
    {
        return $this->candidateFilter->rejectsData($classFQCN, $data, $renameKey);
    }
}
