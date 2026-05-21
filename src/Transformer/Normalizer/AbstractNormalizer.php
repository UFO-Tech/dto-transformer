<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerInterface;

abstract class AbstractNormalizer implements PropertyNormalizerInterface
{
    protected ?PropertyNormalizerChainInterface $chainNormalizer = null;

    public function setChainNormalizer(PropertyNormalizerChainInterface $chainNormalization): void
    {
        $this->chainNormalizer = $chainNormalization;
    }
}
