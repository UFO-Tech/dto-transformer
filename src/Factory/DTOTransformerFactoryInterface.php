<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\DTOTransformer;

interface DTOTransformerFactoryInterface
{
    /**
     * @param class-string<DTOTransformer> $class
     */
    public function create(string $class = DTOTransformer::class): DTOTransformer;
}
