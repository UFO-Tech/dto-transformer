<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\Transformer\DTOToArrayTransformer;
use Ufo\DTO\Transformer\DTOToArrayTransformerInterface;

class DefaultDTOTransformerToArrayFactory implements DTOTransformerToArrayFactoryInterface
{
    public function __construct(
        protected NormalizerChainFactoryInterface $propertyNormalizerFactory,
    ) {}

    public function create(): DTOToArrayTransformerInterface
    {
        return new DTOToArrayTransformer($this->propertyNormalizerFactory->create());
    }
}