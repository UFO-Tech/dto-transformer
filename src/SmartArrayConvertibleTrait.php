<?php

namespace Ufo\DTO;

trait SmartArrayConvertibleTrait
{
    use JsonSerializableTrait;
    public function toArray(): array
    {
        return DTOTransformer::toArray($this, asSmartArray: true);
    }
}