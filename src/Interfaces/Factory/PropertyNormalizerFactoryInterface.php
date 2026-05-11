<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Factory;

use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;

interface PropertyNormalizerFactoryInterface
{
    public function create(): PropertyNormalizerChainInterface;
}