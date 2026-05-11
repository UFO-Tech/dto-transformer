<?php

namespace Ufo\DTO\Interfaces\Factory;

use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;

interface DTOTransformerFromArrayFactoryInterface
{
    /**
     * @param iterable<DTOFromArrayTransformerInterface> $transformers
     */
    public function create(
        iterable $transformers = [],
    ): DTOFromArrayTransformerInterface;
}