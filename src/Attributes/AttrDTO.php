<?php

namespace Ufo\DTO\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
class AttrDTO
{
    public function __construct(
        public readonly string $dtoFQCN,
        public readonly bool $collection = false,
        public readonly array $renameKeys = [],
        public readonly ?string $transformerFQCN = null
    ) {}
}