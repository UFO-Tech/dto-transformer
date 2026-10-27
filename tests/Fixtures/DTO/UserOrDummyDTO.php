<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

final class UserOrDummyDTO
{
    public function __construct(
        public UserDto|DummyDTO $item,
    ) {}
}
