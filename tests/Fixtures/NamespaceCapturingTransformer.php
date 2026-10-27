<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures;

use Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface;
use Ufo\DTO\Tests\Fixtures\DTO\ValidDTO;

final class NamespaceCapturingTransformer implements StaticDTOFromArrayTransformerInterface
{
    /** @var array<string, string> */
    public static array $namespaces = [];

    public static function fromArray(string $classFQCN, array $data, array $renameKey = [], array $namespaces = []): object
    {
        self::$namespaces = $namespaces;

        return new ValidDTO($data['name']);
    }

    public static function isSupportClass(string $classFQCN): bool
    {
        return $classFQCN === ValidDTO::class;
    }
}
