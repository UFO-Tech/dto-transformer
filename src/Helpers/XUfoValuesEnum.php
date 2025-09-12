<?php

namespace Ufo\DTO\Helpers;

enum XUfoValuesEnum: string
{
    case CORE = 'x-ufo';

    case ENUM = self::CORE->value . '-enum';
    case ENUM_NAME = self::ENUM->value . '-name';
    case ASSERTIONS = self::CORE->value . '-assertions';
    case X_METHOD = 'x-method';
}
