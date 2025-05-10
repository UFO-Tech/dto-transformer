<?php

namespace Ufo\DTO;

trait ArrayConvertibleTrait
{
    public function toArray(): array
    {
        return DTOTransformer::toArray($this);
    }
}