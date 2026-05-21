<?php

declare(strict_types=1);

namespace Ufo\DTO\Interfaces\Meta;

use ReflectionParameter;
use ReflectionProperty;

interface StrictModeResolverInterface
{
    public function resolve(
        ReflectionProperty|ReflectionParameter $reflection,
        bool $default = false,
    ): bool;
}
