<?php

namespace Ufo\DTO;

trait ArrayConvertibleTrait
{
    use JsonSerializableTrait;
    public function toArray(): array
    {
        return DTOTransformer::toArray($this);
    }
}