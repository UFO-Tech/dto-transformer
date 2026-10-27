<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface;

final class ValueErrorTransformer implements StaticDTOFromArrayTransformerInterface
{
    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = [], array $context = []): object
    {
        throw new \ValueError('inner reason from transformer');
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return true;
    }
}
