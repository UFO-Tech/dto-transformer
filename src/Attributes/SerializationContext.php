<?php

declare(strict_types=1);

namespace Ufo\DTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SerializationContext
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public array $context = [],
    ) {}
}
