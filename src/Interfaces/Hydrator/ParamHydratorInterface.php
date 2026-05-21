<?php

namespace Ufo\DTO\Interfaces\Hydrator;

use Ufo\DTO\VO\TransformationContext;

interface ParamHydratorInterface
{
    public function supports(array $schema): bool;

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
