<?php

namespace Ufo\DTO\Interfaces;

interface IDTOFromArrayTransformer
{
    public static function fromArray(string $classFQCN, array $data, array $renameKey = []): object;

    public static function isSupportClass(string $classFQCN): bool;

}