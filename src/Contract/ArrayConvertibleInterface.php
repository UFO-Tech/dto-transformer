<?php

namespace Ufo\DTO\Contract;

interface ArrayConvertibleInterface
{
    public function toArray(bool $publicOnly = true): array;
}
