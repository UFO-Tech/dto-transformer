<?php

namespace Ufo\DTO\Transformer\Context;

readonly class TransformKey
{
    public function __construct(
        public string $dtoKey,
        public ?string $dataKey = null
    ) {}

}