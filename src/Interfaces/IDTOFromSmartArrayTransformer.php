<?php

declare(strict_types = 1);

namespace Ufo\DTO\Interfaces;

use Ufo\DTO\Exceptions\NotSupportDTOException;

interface IDTOFromSmartArrayTransformer
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $renameKey
     * @param array<string, string> $namespaces
     * @return object
     * @throws NotSupportDTOException
     */
    public static function fromSmartArray(array $data, array $renameKey = [], array $namespaces = []): object;
}