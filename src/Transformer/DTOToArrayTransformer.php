<?php

namespace Ufo\DTO\Transformer;

use ReflectionException;
use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Exceptions\NotInitializeException;
use Ufo\DTO\Exceptions\NotSupportDTOException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\DTOToArrayTransformerInterface;
use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Interfaces\IDTOToArrayTransformer;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\VO\NormalizationContext;
use Ufo\DTO\VO\TransformKeyVO;

use function array_key_exists;
use function class_exists;
use function count;
use function explode;
use function implode;
use function sprintf;
use function str_contains;

class DTOToArrayTransformer implements DTOToArrayTransformerInterface
{
    public function __construct(
        protected PropertyNormalizerChainInterface $propertyNormalizer
    ) {}

    /**
     * Converts a DTO object to an associative array.
     *
     * @param object $dto The object to convert.
     * @param array<string,string|null> $renameKey
     *
     * @return array An associative array of the object's properties.
     */
    public function transformToArray(
        object $dto,
        array $renameKey = [],
        bool $asSmartArray = false,
        bool $publicOnly = true,
        array $context = [],
    ): array
    {
        return $this->propertyNormalizer->normalize(
            $dto,
            NormalizationContext::create($renameKey, $asSmartArray, $publicOnly, $context)
        );
    }

    public function support(string $classFQCN): bool
    {
        return class_exists($classFQCN);
    }
}
