<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\Transformer\Normalizer\NormalizerChainInterface;

interface NormalizerChainFactoryInterface
{
    public function create(): NormalizerChainInterface;
}