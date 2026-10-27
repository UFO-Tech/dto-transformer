<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Transformer\Context\TransformationContext;

interface HydratorChainInterface
{
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
