<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Type;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

use function strtolower;

/**
 * Перевірка, чи можна присвоїти значення властивості або параметру з їхнім оголошеним типом.
 */
class TypeCompatibility
{
    public function canAssign(mixed $value, ReflectionParameter|ReflectionProperty $target): bool
    {
        $type = $target->getType();

        if ($type === null) return true;
        if ($value === null) return $type->allowsNull();

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($this->matches($value, $innerType, $target)) return true;
            }
            return false;
        }

        return $this->matches($value, $type, $target);
    }

    protected function matches(
        mixed $value,
        ReflectionType $type,
        ReflectionParameter|ReflectionProperty $target,
    ): bool
    {
        if ($type instanceof ReflectionIntersectionType) {
            return array_all($type->getTypes(), fn ($innerType) => $this->matches($value, $innerType, $target));
        }

        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();
        $builtinType = TypeHintResolver::tryFrom($typeName);
        if ($builtinType !== null) {
            return $builtinType->matchType($value);
        }

        if ($type->isBuiltin()) {
            return false;
        }

        $declaringClass = $target->getDeclaringClass();
        $className = match (strtolower($typeName)) {
            'self', 'static' => $declaringClass?->getName(),
            'parent' => ($declaringClass?->getParentClass() ?: null)?->getName(),
            default => $typeName,
        };

        return $className !== null && $value instanceof $className;
    }
}
