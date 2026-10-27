<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use ReflectionProperty;

interface SerializationContextAttributeReaderInterface
{
    /**
     * @return array<string, mixed> значення контексту, які властивість додає до контексту свого значення
     */
    public function read(ReflectionProperty $property): array;
}
