<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Normalizer;

abstract class AbstractNormalizer implements NormalizerInterface
{
    protected ?NormalizerChainInterface $chainNormalizer = null;

    public function setChainNormalizer(NormalizerChainInterface $chainNormalization): void
    {
        $this->chainNormalizer = $chainNormalization;
    }
}
