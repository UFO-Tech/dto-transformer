<?php

namespace Ufo\DTO\Transformer;

interface DTOToArrayTransformerInterface
{
    public function transformToArray(
        object $dto,
        array $renameKey = [],
        bool $asSmartArray = false,
        bool $publicOnly = true,
        array $context = [],
    ): array;

    public function support(string $classFQCN): bool;
}