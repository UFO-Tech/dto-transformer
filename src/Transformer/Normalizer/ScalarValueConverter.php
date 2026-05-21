<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;
use Ufo\DTO\VO\NormalizationContext;

class ScalarValueConverter implements PropertyNormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return $data === null || is_scalar($data);
    }

    public function normalize(mixed $data, NormalizationContext $context): mixed
    {
        return $data;
    }
}
