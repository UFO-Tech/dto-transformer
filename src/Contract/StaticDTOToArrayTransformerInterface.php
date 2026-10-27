<?php

namespace Ufo\DTO\Contract;

interface StaticDTOToArrayTransformerInterface
{
    public static function toArray(object $dto, array $renameKey = [], bool $asSmartArray = false, bool $publicOnly = true): array;
}
