<?php

namespace Ufo\DTO\Interfaces\Factory;

use Ufo\DTO\Interfaces\DTOToArrayTransformerInterface;

interface DTOTransformerToArrayFactoryInterface
{
    public function create(): DTOToArrayTransformerInterface;
}