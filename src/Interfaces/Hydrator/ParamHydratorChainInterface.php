<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Hydrator;

use Ufo\DTO\VO\TransformationContext;

interface ParamHydratorChainInterface
{
    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
