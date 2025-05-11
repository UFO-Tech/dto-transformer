<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

final class AliasDTO
{
    public function __construct(
        public readonly int $id,
        public string $name
    ) {}
}