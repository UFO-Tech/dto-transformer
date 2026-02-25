<?php

namespace Ufo\DTO;

trait ArrayConvertibleTrait
{
    use JsonSerializableTrait;
    public function toArray(bool $publicOnly = true): array
    {
        return DTOTransformer::toArray($this, publicOnly: $publicOnly);
    }
}