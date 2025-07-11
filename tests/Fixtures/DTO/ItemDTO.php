<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

class ItemDTO
{
    public function __construct(
        public UserDto|DummyDTO $friend
    ) {}
}
