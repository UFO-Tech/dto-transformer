<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Schema\PropertySchema;
use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Context\TransformKey;
use Ufo\DTO\Transformer\Normalizer\Cache\PropertyContextCache;

class DTONormalizer extends AbstractNormalizer implements NormalizerInterface, DTONormalizerInterface
{
    public function __construct(
        protected ClassSchemaProviderInterface $schemaProvider,
        protected PropertyContextCache $contextCache = new PropertyContextCache(),
    ) {}

    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return is_object($data);
    }

    public function normalize(mixed $data, NormalizationContext $context): array
    {
        return $this->normalizeObject($data, $context);
    }

    public function normalizeObject(object $object, NormalizationContext $context): array
    {
        $className = $object::class;
        $schema = $this->schemaProvider->schema($className);
        $reflection = $schema->reflection;
        $properties = $context->publicOnly() ? $schema->publicExportedProperties : $schema->exportedProperties;
        $propertyContexts = $this->contextCache->get($context, $className)
            ?? $this->contextCache->set($context, $className, $this->propertyContexts($properties, $reflection, $context));
        $renameKey = $context->renameKey();
        $array = [];

        foreach ($properties as $propertySchema) {
            $dataKey = $renameKey === [] ? $propertySchema->name : $this->propertyKey($propertySchema->property, $renameKey)->dataKey;
            if (!$dataKey) continue;

            $array[$dataKey] = $this->chainNormalizer->normalize(
                $propertySchema->property->getValue($object),
                $propertyContexts[$propertySchema->name],
            );
        }

        if ($context->asSmartArray()) {
            $array[BaseDTOFromArrayTransformer::DTO_CLASSNAME] = $object instanceof AttrDTO
                ? $reflection->getShortName()
                : $reflection->getName();
        }

        return $array;
    }

    /**
     * @param list<PropertySchema> $properties
     * @return array<string, NormalizationContext> контекст значення за іменем властивості
     */
    protected function propertyContexts(array $properties, ReflectionClass $reflection, NormalizationContext $context): array
    {
        $contexts = [];
        foreach ($properties as $propertySchema) {
            $contexts[$propertySchema->name] = $context->forPropertyValue(
                $propertySchema->property,
                $reflection,
                $propertySchema->serializationContext,
            );
        }

        return $contexts;
    }

    /**
     * @param array<string, string|null> $renameKey
     */
    protected function propertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): TransformKey
    {
        $dtoKey = $property->getName();
        $dataKey = array_key_exists($dtoKey, $renameKey) ? $renameKey[$dtoKey] : $dtoKey;

        return new TransformKey($dtoKey, $dataKey);
    }
}
