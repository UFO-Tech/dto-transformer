<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces;

interface DTOFromSmartArrayTransformerInterface
{
    public function transformFromSmartArray(
        array $data,
        array $renameKey = [],
        array $namespaces = [],
        array $context = [],
    ): object;
}
