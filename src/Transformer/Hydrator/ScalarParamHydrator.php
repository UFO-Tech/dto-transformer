<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Exceptions\BadParamException;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;
use function in_array;
use function is_array;
use function sprintf;

class ScalarParamHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        $type = $schema[TypeHintResolver::TYPE] ?? null;

        return $type !== null
            && !isset($schema[TypeHintResolver::ONE_OFF])
            && !isset($schema[TypeHintResolver::ITEMS])
            && !isset($schema[TypeHintResolver::CLASS_FQCN])
            && in_array($type, [
                TypeHintResolver::STRING->value,
                TypeHintResolver::INTEGER->value,
                TypeHintResolver::NUMBER->value,
                TypeHintResolver::BOOLEAN->value,
                TypeHintResolver::NULL->value,
            ], true);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        $type = $schema[TypeHintResolver::TYPE] ?? null;

        if ($type === TypeHintResolver::NULL->value) {
            if ($value === null) {
                return null;
            }

            throw new BadParamException(sprintf('Normalizer does not match schema type %s', $type,));
        }

        if ($type === TypeHintResolver::OBJECT->value) {
            if (is_array($value)) {
                return $value;
            }
            throw new BadParamException(sprintf('Normalizer does not match schema type %s', $type,));
        }

        $phpType = TypeHintResolver::jsonSchemaToPhp($type);

        if (TypeHintResolver::tryFrom($phpType)?->matchType($value)) {
            return $value;
        }

        if ($context->strict) {
            throw new BadParamException(sprintf('Normalizer does not match schema type %s', $type));
        }

        return $value;
    }
}
