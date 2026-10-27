<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;

final class CountingUpperTransformer implements StaticDTOFromArrayTransformerInterface
{
    public static int $supportChecks = 0;

    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = [], array $context = []): object
    {
        return new ValidDTO(strtoupper($data['name']));
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        static::$supportChecks++;

        return $classFQCN === ValidDTO::class;
    }
}
