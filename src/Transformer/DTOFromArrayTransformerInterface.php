<?php

namespace Ufo\DTO\Transformer;

interface DTOFromArrayTransformerInterface
{
    public function transformFromArray(
        string $classFQCN,
        array $data,
        array $renameKey = [],
        array $namespaces = [],
        array $context = [],
    ): object;

    public function support(string $classFQCN): bool;
}
