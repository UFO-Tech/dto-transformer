<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

final class LegacyReverseNormalizer implements PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return is_string($data);
    }

    public function normalize(mixed $data, NormalizationContext $context): mixed
    {
        return strrev($data);
    }
}
