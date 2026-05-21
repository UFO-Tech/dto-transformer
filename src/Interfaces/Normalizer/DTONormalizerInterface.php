<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Normalizer;

use Ufo\DTO\VO\NormalizationContext;

interface DTONormalizerInterface
{
    public function normalizeObject(object $object, NormalizationContext $context): array;
}
