<?php

/**
 * Оголошення імен 3.x лише для індексу IDE; не завантажується і не виконується.
 * @see aliases.php
 */

declare(strict_types=1);

namespace Ufo\DTO\Interfaces {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Contract\ArrayConstructibleInterface */
        interface IArrayConstructible extends \Ufo\DTO\Contract\ArrayConstructibleInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Contract\ArrayConvertibleInterface */
        interface IArrayConvertible extends \Ufo\DTO\Contract\ArrayConvertibleInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface */
        interface IDTOFromArrayTransformer extends \Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Contract\StaticDTOToArrayTransformerInterface */
        interface IDTOToArrayTransformer extends \Ufo\DTO\Contract\StaticDTOToArrayTransformerInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Contract\ArrayConstructibleInterface */
        interface ArrayConstructibleInterface extends \Ufo\DTO\Contract\ArrayConstructibleInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Contract\ArrayConvertibleInterface */
        interface ArrayConvertibleInterface extends \Ufo\DTO\Contract\ArrayConvertibleInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\DTOFromArrayTransformerInterface */
        interface DTOFromArrayTransformerInterface extends \Ufo\DTO\Transformer\DTOFromArrayTransformerInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\DTOToArrayTransformerInterface */
        interface DTOToArrayTransformerInterface extends \Ufo\DTO\Transformer\DTOToArrayTransformerInterface {}
    }
}

namespace Ufo\DTO\Interfaces\Factory {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Factory\DTOTransformerFactoryInterface */
        interface DTOTransformerFactoryInterface extends \Ufo\DTO\Factory\DTOTransformerFactoryInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Factory\DTOTransformerFromArrayFactoryInterface */
        interface DTOTransformerFromArrayFactoryInterface extends \Ufo\DTO\Factory\DTOTransformerFromArrayFactoryInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Factory\DTOTransformerToArrayFactoryInterface */
        interface DTOTransformerToArrayFactoryInterface extends \Ufo\DTO\Factory\DTOTransformerToArrayFactoryInterface {}
    }
}

namespace Ufo\DTO\Interfaces\Hydrator {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Hydrator\HydratorInterface */
        interface ParamHydratorInterface extends \Ufo\DTO\Transformer\Hydrator\HydratorInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Hydrator\HydratorChainInterface */
        interface ParamHydratorChainInterface extends \Ufo\DTO\Transformer\Hydrator\HydratorChainInterface {}
    }
}

namespace Ufo\DTO\Interfaces\Normalizer {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Normalizer\NormalizerInterface */
        interface PropertyNormalizerInterface extends \Ufo\DTO\Transformer\Normalizer\NormalizerInterface {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Normalizer\NormalizerChainInterface */
        interface PropertyNormalizerChainInterface extends \Ufo\DTO\Transformer\Normalizer\NormalizerChainInterface {}
    }
}

namespace Ufo\DTO\Transformer\Hydrator {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Hydrator\AbstractHydrator */
        abstract class AbstractParamHydrator extends \Ufo\DTO\Transformer\Hydrator\AbstractHydrator {}
    }
}

namespace Ufo\DTO\VO {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Schema\Enum\EnumDefinition */
        readonly class EnumVO extends \Ufo\DTO\Schema\Enum\EnumDefinition {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Context\TransformationContext */
        readonly class TransformationContext extends \Ufo\DTO\Transformer\Context\TransformationContext {}

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Transformer\Context\NormalizationContext */
        class NormalizationContext extends \Ufo\DTO\Transformer\Context\NormalizationContext {}
    }
}

namespace Ufo\DTO\Helpers {
    if (false) {
        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Schema\Type\TypeHintResolver */
        class_alias('Ufo\DTO\Schema\Type\TypeHintResolver', 'Ufo\DTO\Helpers\TypeHintResolver');

        /** @deprecated since 4.0, will be removed in 4.1, use \Ufo\DTO\Schema\Enum\EnumResolver */
        class_alias('Ufo\DTO\Schema\Enum\EnumResolver', 'Ufo\DTO\Helpers\EnumResolver');
    }
}
