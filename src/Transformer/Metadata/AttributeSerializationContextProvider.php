<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Metadata;

use ReflectionProperty;
use Ufo\DTO\Attributes\SerializationContext;
use Ufo\DTO\Interfaces\Normalizer\SerializationContextProviderInterface;
use Ufo\DTO\VO\NormalizationContext;

class AttributeSerializationContextProvider implements SerializationContextProviderInterface
{
    public function contextForProperty(ReflectionProperty $property, NormalizationContext $context): NormalizationContext
    {
        $values = [];
        foreach ($property->getAttributes(SerializationContext::class) as $attribute) {
            $values = [
                ...$values,
                ...$attribute->newInstance()->context,
            ];
        }

        return $context->withValues($values);
    }
}
