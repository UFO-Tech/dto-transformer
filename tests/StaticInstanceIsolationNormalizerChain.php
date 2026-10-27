<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use Ufo\DTO\Transformer\Context\NormalizationContext;
use Ufo\DTO\Transformer\Normalizer\NormalizerChainInterface;

final class StaticInstanceIsolationNormalizerChain implements NormalizerChainInterface
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
