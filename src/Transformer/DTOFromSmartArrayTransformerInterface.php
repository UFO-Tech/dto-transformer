<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer;

interface DTOFromSmartArrayTransformerInterface
{
    public function transformFromSmartArray(
        array $data,
        array $renameKey = [],
        array $namespaces = [],
        array $context = [],
    ): object;
}
