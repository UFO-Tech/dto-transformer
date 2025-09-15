<?php

namespace Ufo\DTO\Helpers;

enum XUfoValuesEnum: string
{
    case CORE = 'x-ufo';

    case ENUM = self::CORE->value . '-enum';
    case ASSERTIONS = self::CORE->value . '-assertions';
    case X_METHOD = 'x-method';

    case ENUM_NAME = 'name';
    case ENUM_VALUES = 'values';
}
