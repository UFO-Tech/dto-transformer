<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Normalizer;

use ReflectionProperty;
use Ufo\DTO\VO\NormalizationContext;

interface SerializationContextProviderInterface
{
    public function contextForProperty(ReflectionProperty $property, NormalizationContext $context): NormalizationContext;
}
