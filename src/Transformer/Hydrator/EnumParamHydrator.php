<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Converter\EnumConverter;
use Ufo\DTO\VO\TransformationContext;
use UnitEnum;

class EnumParamHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        return EnumResolver::findEnumNameInJsonSchema($schema) !== null;
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): UnitEnum
    {
        $enumName = EnumResolver::findEnumNameInJsonSchema($schema);

        $enumFQCN = TypeHintResolver::typeWithNamespaceOrDefault(
            $enumName,
            $context->namespaces,
            BaseDTOFromArrayTransformer::DTO_NS_KEY,
        ) ?? $enumName;

        return EnumConverter::toEnum($enumFQCN, $value);
    }
}
