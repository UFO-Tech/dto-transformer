<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Factory;

use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;

interface ParamHydratorFactoryInterface
{
    public function create(): ParamHydratorChainInterface;
}
