<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;

class DocblockDTO
{
    /**
     * @param array<UserDto|DummyDTO|IntEnum> $collection
     */
    public function __construct(
        public string $name,
        public array $collection
    ) {}
}