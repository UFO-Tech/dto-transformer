<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Transformer\Context\NormalizationContext;

class ScalarNormalizer implements NormalizerInterface
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
