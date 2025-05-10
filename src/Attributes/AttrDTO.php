<?php

namespace Ufo\DTO\Attributes;

abstract class AttrDTO
{
    public function __construct(
        public readonly string $dtoFQCN,
        public readonly bool $collection = false,
        public readonly array $renameKeys = [],
        public readonly ?string $transformerFQCN = null
    ) {}
}