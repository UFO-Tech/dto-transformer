<?php

declare(strict_types=1);

namespace Ufo\DTO\Attributes;

use Attribute;

#[Attribute(
    Attribute::TARGET_CLASS
    | Attribute::TARGET_METHOD
    | Attribute::TARGET_FUNCTION
    | Attribute::TARGET_PROPERTY
    | Attribute::TARGET_PARAMETER
)]
readonly class StrictMode
{
    public function __construct(
        public bool $enabled = true,
    ) {}
}
