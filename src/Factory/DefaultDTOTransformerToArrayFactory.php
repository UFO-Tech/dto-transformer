<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\Interfaces\DTOToArrayTransformerInterface;
use Ufo\DTO\Interfaces\Factory\DTOTransformerToArrayFactoryInterface;
use Ufo\DTO\Interfaces\Factory\PropertyNormalizerFactoryInterface;
use Ufo\DTO\Transformer\DTOToArrayTransformer;

class DefaultDTOTransformerToArrayFactory implements DTOTransformerToArrayFactoryInterface
{
    public function __construct(
        protected PropertyNormalizerFactoryInterface $propertyNormalizerFactory,
    ) {}

    public function create(): DTOToArrayTransformerInterface
    {
        return new DTOToArrayTransformer($this->propertyNormalizerFactory->create());
    }
}