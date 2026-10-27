<?php

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Transformer\Context\TransformationContext;

interface HydratorInterface
{
    public function supports(array $schema): bool;

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
