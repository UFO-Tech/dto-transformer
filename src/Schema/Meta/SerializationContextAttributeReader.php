<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use ReflectionProperty;
use Ufo\DTO\Attributes\SerializationContext;

/**
 * Значення `#[SerializationContext]` властивості без кешу; результат зберігає схема класу.
 */
class SerializationContextAttributeReader implements SerializationContextAttributeReaderInterface
{
    public function read(ReflectionProperty $property): array
    {
        $values = [];
        foreach ($property->getAttributes(SerializationContext::class) as $attribute) {
            $values = [
                ...$values,
                ...$attribute->newInstance()->context,
            ];
        }

        return $values;
    }
}
