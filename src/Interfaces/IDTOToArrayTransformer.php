<?php

namespace Ufo\DTO\Interfaces;

interface IDTOToArrayTransformer
{
    public static function toArray(object $dto, array $renameKey = [], bool $asSmartArray = false, bool $publicOnly = true): array;
}