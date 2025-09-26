<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;


use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;

class DTOWithEnums
{
    /**
     * @param array<IntEnum|StringEnum> $enums
     */
    public function __construct(
        public array $enums
    ) {}

}