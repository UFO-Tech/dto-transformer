<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Transformer\Context\NormalizationContext;

interface DTONormalizerInterface
{
    public function normalizeObject(object $object, NormalizationContext $context): array;
}
