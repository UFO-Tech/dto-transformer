<?php

namespace Ufo\DTO;

trait SmartArrayConvertibleTrait
{
    use JsonSerializableTrait;
    public function toArray(bool $publicOnly = true): array
    {
        return DTOTransformer::toArray($this, asSmartArray: true, publicOnly: $publicOnly);
    }
}