<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;

final class NoInterfaceTransformer
{
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        return new ValidDTO('from-no-interface');
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return true;
    }
}
