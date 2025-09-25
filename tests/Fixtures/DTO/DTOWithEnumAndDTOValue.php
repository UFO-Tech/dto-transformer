<?php

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;
use Ufo\DTO\Tests\Fixtures\Enum\OnlyNameEnum;
use Ufo\DTO\Tests\Fixtures\Enum\StringEnum;

class DTOWithEnumAndDTOValue
{
    public function __construct(
        public StringEnum $stringEnum,
        public IntEnum $intEnum,
        public OnlyNameEnum $onlyNameEnum,
        public DummyDTO $dummyDTO
    ) {}
}