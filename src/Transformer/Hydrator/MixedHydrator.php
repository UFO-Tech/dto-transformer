<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

class MixedHydrator implements ParamHydratorInterface
{
    public function supports(array $schema): bool
    {
        return ($schema[TypeHintResolver::TYPE] ?? null) === TypeHintResolver::ANY->value
            || ($schema[TypeHintResolver::TYPE] ?? null) === TypeHintResolver::MIXED->value;
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        return $value;
    }
}
