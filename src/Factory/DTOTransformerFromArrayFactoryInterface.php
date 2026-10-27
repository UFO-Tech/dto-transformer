<?php

namespace Ufo\DTO\Factory;

use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;

interface DTOTransformerFromArrayFactoryInterface
{
    /**
     * @param iterable<DTOFromArrayTransformerInterface> $transformers
     */
    public function create(
        iterable $transformers = [],
    ): DTOFromArrayTransformerInterface;
}