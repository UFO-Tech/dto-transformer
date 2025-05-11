<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

final class DummyDTO
{
    public function __construct(
        public readonly int $id,
        public string $name
    ) {}
}
