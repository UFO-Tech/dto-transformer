<?php

namespace Ufo\DTO\Contract;

interface StaticDTOFromArrayTransformerInterface
{
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object;

    public static function isSupportClass(string $classFQCN): bool;
}
