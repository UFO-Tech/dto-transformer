<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\VO\NormalizationContext;

final class StaticInstanceIsolationPropertyNormalizerChain implements PropertyNormalizerChainInterface
{
    public function supports(mixed $data, NormalizationContext $context): bool
    {
        return true;
    }

    public function normalize(mixed $data, NormalizationContext $context): mixed
    {
        return [];
    }
}
