<?php

namespace Ufo\DTO\Tests\Fixtures\Enum;

enum StringEnum: string
{
    case A = 'a';
    case B = 'b';
    case C = 'c';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
