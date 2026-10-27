<?php

declare(strict_types=1);

/**
 * Імена 3.x як deprecated-аліаси імен 4.0; видаляються в 4.1.
 * @see aliases-stubs.php
 */

namespace Ufo\DTO;

const DEPRECATED_ALIASES = [
    'Ufo\DTO\Interfaces\IArrayConstructible' => Contract\ArrayConstructibleInterface::class,
    'Ufo\DTO\Interfaces\IArrayConvertible' => Contract\ArrayConvertibleInterface::class,
    'Ufo\DTO\Interfaces\IDTOFromArrayTransformer' => Contract\StaticDTOFromArrayTransformerInterface::class,
    'Ufo\DTO\Interfaces\IDTOToArrayTransformer' => Contract\StaticDTOToArrayTransformerInterface::class,
    'Ufo\DTO\Interfaces\ArrayConstructibleInterface' => Contract\ArrayConstructibleInterface::class,
    'Ufo\DTO\Interfaces\ArrayConvertibleInterface' => Contract\ArrayConvertibleInterface::class,
    'Ufo\DTO\VO\EnumVO' => Schema\Enum\EnumDefinition::class,
    'Ufo\DTO\Helpers\TypeHintResolver' => Schema\Type\TypeHintResolver::class,
    'Ufo\DTO\Helpers\EnumResolver' => Schema\Enum\EnumResolver::class,
    'Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface' => Transformer\DTOFromArrayTransformerInterface::class,
    'Ufo\DTO\Interfaces\DTOToArrayTransformerInterface' => Transformer\DTOToArrayTransformerInterface::class,
    'Ufo\DTO\Interfaces\Factory\DTOTransformerFactoryInterface' => Factory\DTOTransformerFactoryInterface::class,
    'Ufo\DTO\Interfaces\Factory\DTOTransformerFromArrayFactoryInterface' => Factory\DTOTransformerFromArrayFactoryInterface::class,
    'Ufo\DTO\Interfaces\Factory\DTOTransformerToArrayFactoryInterface' => Factory\DTOTransformerToArrayFactoryInterface::class,
    'Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface' => Transformer\Hydrator\HydratorInterface::class,
    'Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface' => Transformer\Hydrator\HydratorChainInterface::class,
    'Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface' => Transformer\Normalizer\NormalizerInterface::class,
    'Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface' => Transformer\Normalizer\NormalizerChainInterface::class,
    'Ufo\DTO\Transformer\Hydrator\AbstractParamHydrator' => Transformer\Hydrator\AbstractHydrator::class,
    'Ufo\DTO\VO\TransformationContext' => Transformer\Context\TransformationContext::class,
    'Ufo\DTO\VO\NormalizationContext' => Transformer\Context\NormalizationContext::class,
];

foreach (DEPRECATED_ALIASES as $deprecated => $replacement) {
    class_alias($replacement, $deprecated);
}
