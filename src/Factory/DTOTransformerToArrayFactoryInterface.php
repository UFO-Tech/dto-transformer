<?php

namespace Ufo\DTO\Factory;

use Ufo\DTO\Transformer\DTOToArrayTransformerInterface;

interface DTOTransformerToArrayFactoryInterface
{
    public function create(): DTOToArrayTransformerInterface;
}