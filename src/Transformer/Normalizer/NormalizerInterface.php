<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Transformer\Context\NormalizationContext;

interface NormalizerInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool;

    public function normalize(mixed $data, NormalizationContext $context): mixed;
}
