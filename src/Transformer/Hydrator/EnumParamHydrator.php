<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Exceptions\InvalidEnumValueException;
use Ufo\DTO\Helpers\EnumResolver;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\Transformer\Converter\EnumConverter;
use Ufo\DTO\VO\TransformationContext;
use UnitEnum;
use function get_debug_type;
use function is_int;
use function is_string;
use function sprintf;

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
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidEnumValueException(sprintf(
                'Enum value must be string or int, %s given.',
                get_debug_type($value),
            ));
        }

        $enumName = EnumResolver::findEnumNameInJsonSchema($schema);
        $enumFQCN = $this->resolveEnumFqcn($schema, $enumName, $context);

        return EnumConverter::toEnum($enumFQCN, $value);
    }

    protected function resolveEnumFqcn(
        array $schema,
        string $enumName,
        TransformationContext $context,
    ): string
    {
        $classFQCN = $schema[TypeHintResolver::CLASS_FQCN] ?? null;
        if (is_string($classFQCN) && enum_exists($classFQCN)) {
            return $classFQCN;
        }

        return TypeHintResolver::typeWithNamespaceOrDefault(
                $enumName,
                $context->namespaces(),
                BaseDTOFromArrayTransformer::DTO_NS_KEY,
            )
            ?? $enumName;
    }
}
