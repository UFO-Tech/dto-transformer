<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Meta;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Клас, у файлі якого оголошено reflection-об'єкт; `null` для параметра функції поза класом.
 */
final class ReflectionDeclaringClass
{
    public static function of(ReflectionClass|ReflectionProperty|ReflectionParameter $reflection): ?ReflectionClass
    {
        if ($reflection instanceof ReflectionClass) {
            return $reflection;
        }

        if ($reflection instanceof ReflectionProperty) {
            return $reflection->getDeclaringClass();
        }

        $function = $reflection->getDeclaringFunction();

        return $function instanceof ReflectionMethod ? $function->getDeclaringClass() : null;
    }
}
