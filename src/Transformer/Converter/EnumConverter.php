<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Converter;

use BackedEnum;
use Ufo\DTO\Exceptions\BadParamException;
use UnitEnum;
use function is_int;
use function is_string;
use function preg_match;
use function sprintf;
use function strcasecmp;

class EnumConverter
{
    /** @param class-string<UnitEnum|BackedEnum> $enumFQCN */
    public static function toEnum(
        string $enumFQCN,
        string|int $value,
    ): UnitEnum
    {
        if (is_subclass_of($enumFQCN, BackedEnum::class)) {
            if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1 && static::isIntBackedEnum($enumFQCN)) {
                $value = (int) $value;
            }

            return $enumFQCN::tryFrom($value) ?? throw new BadParamException(sprintf(
                'Invalid value "%s" for enum %s',
                $value,
                $enumFQCN
            ));
        }

        foreach ($enumFQCN::cases() as $case) {
            if (strcasecmp($case->name, (string) $value) === 0) {
                return $case;
            }
        }

        throw new BadParamException(sprintf('Invalid value "%s" for enum %s', $value, $enumFQCN));
    }

    /** @param class-string<BackedEnum> $enumFQCN */
    protected static function isIntBackedEnum(string $enumFQCN): bool
    {
        foreach ($enumFQCN::cases() as $case) {
            return is_int($case->value);
        }

        return false;
    }
}
