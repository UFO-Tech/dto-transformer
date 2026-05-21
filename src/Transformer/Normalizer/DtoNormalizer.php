<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use ReflectionParameter;
use ReflectionProperty;
use Ufo\DTO\Attributes\AttrDTO;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Normalizer\DTONormalizerInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\Interfaces\Normalizer\SerializationContextProviderInterface;
use Ufo\DTO\VO\NormalizationContext;
use Ufo\DTO\VO\TransformKeyVO;

class DtoNormalizer extends AbstractNormalizer implements PropertyNormalizerInterface, DTONormalizerInterface
{
    public function __construct(
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected SerializationContextProviderInterface $serializationContextProvider,
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
        $reflection = $this->metadataProvider->classMetadata($object::class)->reflection;
        $array = [];

        foreach ($this->metadataProvider->reflectionProperties($reflection) as $property) {
            $keys = $this->propertyKey($property, $context->renameKey());
            if (!$keys->dataKey || ($context->publicOnly() && !$property->isPublic())) continue;

            $propertyContext = $this->serializationContextProvider->contextForProperty(
                $property,
                $context->forProperty($property, $reflection),
            );

            $array[$keys->dataKey] = $this->chainNormalizer->normalize(
                $property->getValue($object),
                $propertyContext->nextDepth(),
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
     * @param array<string, string|null> $renameKey
     */
    protected function propertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): TransformKeyVO
    {
        $dtoKey = $property->getName();
        $dataKey = array_key_exists($dtoKey, $renameKey) ? $renameKey[$dtoKey] : $dtoKey;

        return new TransformKeyVO($dtoKey, $dataKey);
    }
}
