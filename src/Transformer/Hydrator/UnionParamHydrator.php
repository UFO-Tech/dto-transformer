<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Throwable;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\VO\TransformationContext;

class UnionParamHydrator extends AbstractParamHydrator
{
    public function supports(array $schema): bool
    {
        return isset($schema[TypeHintResolver::ONE_OFF]);
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        foreach ($schema[TypeHintResolver::ONE_OFF] as $subSchema) {
            try {
                return $this->resolveNested(
                    $subSchema,
                    $value,
                    $context->withStrict(true),
                );
            } catch (Throwable) {}
        }

        return $value;
    }
}
