<?php

namespace Ufo\DTO\Contract;

interface ArrayConstructibleInterface
{
    public static function fromArray(array $data, array $renameKey = []): static;
}
