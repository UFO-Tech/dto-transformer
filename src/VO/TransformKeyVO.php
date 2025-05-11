<?php

namespace Ufo\DTO\VO;

readonly class TransformKeyVO
{
    public function __construct(
        public string $dtoKey,
        public ?string $dataKey = null
    ) {}

}