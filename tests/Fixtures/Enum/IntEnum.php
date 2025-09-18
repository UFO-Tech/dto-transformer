<?php

namespace Ufo\DTO\Tests\Fixtures\Enum;

use function array_column;

enum IntEnum: int
{
    case A = 1;
    case B = 2;
    case C = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromValueError(string $value): ?self
    {
        return self::tryFrom(4);
    }
}
