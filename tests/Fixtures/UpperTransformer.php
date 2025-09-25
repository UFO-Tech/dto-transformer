<?php

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Interfaces\IDTOFromArrayTransformer;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;

class UpperTransformer implements IDTOFromArrayTransformer
{
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        return new ValidDTO(strtoupper($data['name']));
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return $classFQCN === ValidDTO::class;
    }

}