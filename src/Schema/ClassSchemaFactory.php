<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Ignore;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\Helpers\StringListPool;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Meta\SerializationContextAttributeReaderInterface;
use Ufo\DTO\Schema\Type\NamespaceKeyAnalyzer;
use Ufo\DTO\Schema\Type\ResolvedTypeSchema;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;
use Ufo\DTO\Schema\Type\TypeSource;

use function array_key_exists;
use function array_keys;
use function array_map;
use function is_array;

/**
 * Будує схему класу й схеми типів його властивостей; опис схеми серіалізовний і може зберігатися між процесами.
 */
class ClassSchemaFactory implements ClassSchemaDescriberInterface
{
    protected const string KEY_NAMESPACES = 'namespaces';
    protected const string KEY_NAMESPACE_KEYS = 'namespaceKeys';
    protected const string KEY_PROPERTIES = 'properties';
    protected const string KEY_IGNORED = 'ignored';
    protected const string KEY_ASSERTIONS = 'assertions';
    protected const string KEY_ATTR_DTO = 'attrDto';
    protected const string KEY_SERIALIZATION_CONTEXT = 'serializationContext';
    protected const string KEY_PROMOTED = 'promoted';
    protected const string KEY_DEFAULT_KIND = 'defaultKind';
    protected const string KEY_DEFAULT_VALUE = 'defaultValue';
    /** Сировина типу властивості: [docType, клас з `use`-namespaces, docStrict] або null. */
    protected const string KEY_TYPE = 'type';
    /** `use`-namespaces класів, що оголошують властивості: один масив на клас. */
    protected const string KEY_TYPE_NAMESPACES = 'typeNamespaces';
    /** Максимум різних наборів ключів namespaces, спільних для класів з однаковими типами. */
    protected const int NAMESPACE_KEY_LISTS_LIMIT = 1024;

    /**
     * @var array<string, list<string>>
     */
    protected array $namespaceKeyLists = [];

    public function __construct(
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected TypeSchemaResolverInterface $typeSchemaResolver,
        protected DefaultValueResolver $defaultValueResolver,
        protected NamespaceKeyAnalyzer $namespaceKeyAnalyzer,
        protected SerializationContextAttributeReaderInterface $serializationContextReader,
    ) {}

    /**
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    public function describe(ReflectionClass $reflectionClass): array
    {
        $parameters = $this->metadataProvider->constructorParameters($reflectionClass);
        $probe = null;
        $namespaceKeys = [];
        $typeNamespaces = [];
        $descriptions = [];

        foreach ($this->metadataProvider->reflectionProperties($reflectionClass) as $property) {
            $name = $property->getName();
            $promoted = $property->isPromoted() && isset($parameters[$name]);
            $valueReflection = $promoted ? $parameters[$name] : $property;
            $probe ??= $reflectionClass->newInstanceWithoutConstructor();
            [$defaultKind, $defaultValue] = $this->defaultValueResolver->strategy($property, $reflectionClass, $probe, $valueReflection);
            $attrDto = $this->metadataProvider->attributes($valueReflection, AttrDTO::class, ReflectionAttribute::IS_INSTANCEOF) !== [];

            $descriptions[$name] = [
                static::KEY_IGNORED => $this->metadataProvider->attributes($property, Ignore::class, ReflectionAttribute::IS_INSTANCEOF) !== [],
                static::KEY_ASSERTIONS => $this->metadataProvider->attributes($property, AttrAssertions::class, ReflectionAttribute::IS_INSTANCEOF) !== [],
                static::KEY_ATTR_DTO => $attrDto,
                static::KEY_SERIALIZATION_CONTEXT => $this->metadataProvider->attributes($property, SerializationContext::class) !== [],
                static::KEY_PROMOTED => $promoted,
                static::KEY_DEFAULT_KIND => $defaultKind,
                static::KEY_DEFAULT_VALUE => $defaultValue,
                static::KEY_TYPE => $attrDto ? null : $this->describeType($valueReflection, $typeNamespaces),
            ];

            foreach ($this->namespaceKeyAnalyzer->keys($valueReflection) as $key) {
                $namespaceKeys[$key] = true;
            }
        }

        return [
            static::KEY_NAMESPACES => $this->metadataProvider->declaringNamespaces($reflectionClass),
            static::KEY_NAMESPACE_KEYS => array_keys($namespaceKeys),
            static::KEY_PROPERTIES => $descriptions,
            static::KEY_TYPE_NAMESPACES => $typeNamespaces,
        ];
    }

    public function redescribe(ReflectionClass $reflectionClass): array
    {
        return $this->describe($reflectionClass);
    }

    /**
     * Схема з опису; null — опис не відповідає класу (наприклад, лишився в кеші від попередньої версії класу).
     *
     * @param array<string, mixed> $description
     */
    public function fromDescription(ReflectionClass $reflectionClass, array $description): ?ClassSchema
    {
        $properties = $this->metadataProvider->reflectionProperties($reflectionClass);
        $parameters = $this->metadataProvider->constructorParameters($reflectionClass);

        if (!$this->isDescriptionValid($description, $properties, $parameters)) {
            return null;
        }

        $propertySchemas = [];
        foreach ($properties as $property) {
            $propertySchemas[] = $this->createPropertySchema(
                $property,
                $description[static::KEY_PROPERTIES][$property->getName()],
                $parameters,
                $reflectionClass,
                $description[static::KEY_TYPE_NAMESPACES],
            );
        }

        return new ClassSchema(
            reflection: $reflectionClass,
            declaringNamespaces: $description[static::KEY_NAMESPACES],
            properties: $propertySchemas,
            namespaceKeys: StringListPool::share($this->namespaceKeyLists, $description[static::KEY_NAMESPACE_KEYS], static::NAMESPACE_KEY_LISTS_LIMIT),
        );
    }

    /**
     * Серіалізовні схеми типів властивостей для одного набору namespaces; null — схему буде розв'язано при використанні.
     *
     * @param array<string, string> $namespaces
     * @return array<string, array{array, bool, bool}|null>
     */
    public function describeTypeSchemas(ClassSchema $schema, array $namespaces): array
    {
        $rows = [];
        foreach ($schema->properties as $property) {
            if ($property->attrDto) {
                $rows[$property->name] = null;
                continue;
            }

            try {
                $resolved = $property->typeSource === null
                    ? $this->typeSchemaResolver->schema($property->valueReflection, $namespaces)
                    : $this->typeSchemaResolver->fromSource($property->typeSource, $namespaces);
                $rows[$property->name] = [$resolved->schema, $resolved->fromDocblock, $resolved->strict];
            } catch (\Throwable) {
                $rows[$property->name] = null;
            }
        }

        return $rows;
    }

    /**
     * @param array<string, array{array, bool, bool}|null> $rows
     * @return array<int, ?ResolvedTypeSchema> за індексом властивості в схемі класу
     */
    public function typeSchemasFromDescription(ClassSchema $schema, array $rows): array
    {
        $typeSchemas = [];
        foreach ($schema->properties as $index => $property) {
            $row = $rows[$property->name] ?? null;
            $typeSchemas[$index] = $row === null
                ? null
                : new ResolvedTypeSchema(schema: $row[0], fromDocblock: $row[1], strict: $row[2]);
        }

        return $typeSchemas;
    }

    /**
     * @param array<string, array<string, string>> $typeNamespaces
     * @return array{string, string, bool}|null
     */
    protected function describeType(ReflectionProperty|ReflectionParameter $reflection, array &$typeNamespaces): ?array
    {
        try {
            $source = $this->typeSchemaResolver->source($reflection);
        } catch (\Throwable) {
            return null;
        }

        $declaringClass = $reflection->getDeclaringClass()?->getName() ?? '';
        $typeNamespaces[$declaringClass] ??= $source->declaringNamespaces;

        return [$source->docType, $declaringClass, $source->docStrict];
    }

    /**
     * @param array<string, mixed> $description
     * @param array<string, ReflectionParameter> $parameters
     * @param array<string, array<string, string>> $typeNamespaces
     */
    protected function createPropertySchema(
        ReflectionProperty $property,
        array $description,
        array $parameters,
        ReflectionClass $reflectionClass,
        array $typeNamespaces,
    ): PropertySchema
    {
        $name = $property->getName();
        $valueReflection = $description[static::KEY_PROMOTED] ? $parameters[$name] : $property;
        $ignored = $description[static::KEY_IGNORED];
        $type = $description[static::KEY_TYPE];

        $assertions = $description[static::KEY_ASSERTIONS]
            ? $property->getAttributes(AttrAssertions::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null
            : null;

        return new PropertySchema(
            property: $property,
            name: $name,
            ignored: $ignored,
            assertions: $assertions?->newInstance(),
            valueReflection: $valueReflection,
            attrDto: $description[static::KEY_ATTR_DTO]
                ? ($valueReflection->getAttributes(AttrDTO::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)?->newInstance()
                : null,
            defaultKind: $description[static::KEY_DEFAULT_KIND],
            defaultValue: $description[static::KEY_DEFAULT_VALUE],
            defaultParameter: $description[static::KEY_DEFAULT_KIND] === PropertySchema::DEFAULT_PARAMETER
                ? $parameters[$name]
                : null,
            fallbackParameter: $ignored || $description[static::KEY_DEFAULT_KIND] === PropertySchema::DEFAULT_LEGACY
                ? $this->defaultValueResolver->fallbackParameter($property, $reflectionClass, $ignored)
                : null,
            typeSource: $type === null ? null : new TypeSource(
                docType: $type[0],
                nativeType: $valueReflection->getType(),
                declaringNamespaces: $typeNamespaces[$type[1]] ?? [],
                docStrict: $type[2],
            ),
            serializationContext: $description[static::KEY_SERIALIZATION_CONTEXT]
                ? $this->serializationContextReader->read($property)
                : [],
        );
    }

    /**
     * @param array<string, mixed> $description
     * @param ReflectionProperty[] $properties
     * @param array<string, ReflectionParameter> $parameters
     */
    protected function isDescriptionValid(array $description, array $properties, array $parameters): bool
    {
        $propertyNames = array_map(static fn (ReflectionProperty $property): string => $property->getName(), $properties);
        $described = $description[static::KEY_PROPERTIES] ?? null;
        if (!is_array($described) || array_keys($described) !== $propertyNames
            || !is_array($description[static::KEY_NAMESPACES] ?? null)
            || !is_array($description[static::KEY_NAMESPACE_KEYS] ?? null)
            || !is_array($description[static::KEY_TYPE_NAMESPACES] ?? null)) {
            return false;
        }

        foreach ($described as $name => $property) {
            if (!array_key_exists(static::KEY_TYPE, $property) || !array_key_exists(static::KEY_SERIALIZATION_CONTEXT, $property)) {
                return false;
            }

            $needsParameter = ($property[static::KEY_PROMOTED] ?? false)
                || ($property[static::KEY_DEFAULT_KIND] ?? null) === PropertySchema::DEFAULT_PARAMETER;
            if ($needsParameter && !isset($parameters[$name])) {
                return false;
            }
        }

        return true;
    }
}
