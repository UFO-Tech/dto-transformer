<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Fixtures\DTO;

use Ufo\DTO\Tests\Fixtures\Enum\IntEnum;

final class DefaultValuesDTO
{
    public int $typed = 5;
    public $untyped;
    public array $list = ['a', 'b'];
    public int $assigned;

    public function __construct(
        public int $promoted = 7,
        public $untypedPromoted = 'ignored',
        public ?IntEnum $nullableEnum = null,
        public IntEnum $level = IntEnum::B,
        public ?\ArrayObject $bag = new \ArrayObject(['x']),
        public \Countable&\ArrayAccess $intersection = new \ArrayObject(['y']),
        int $assigned = 3,
    ) {
        $this->assigned = $assigned;
    }
}
