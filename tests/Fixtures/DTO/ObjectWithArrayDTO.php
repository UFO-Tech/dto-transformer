<?php

declare(strict_types = 1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

class ObjectWithArrayDTO
{
    public function __construct(
        public string $name,
        /**
         * @var array<UserDto>
         */
        public array $data
    ) {}
}