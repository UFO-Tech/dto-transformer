<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;


use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;

class DTOWithEnumValue
{
    public function __construct(
        public StringEnum $stringEnum,
        public IntEnum $intEnum
    ) {}

}