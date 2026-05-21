<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Normalizer;

use Ufo\DTO\VO\NormalizationContext;

interface PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool;

    public function normalize(mixed $data, NormalizationContext $context): mixed;
}
