<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema\Type;

use ReflectionType;

/**
 * Сировина типу властивості, незалежна від namespaces викликача: з неї схема будується для будь-якого набору namespaces.
 */
final class TypeSource
{
    /**
     * @param string $docType тип з `@var` / `@param` конструктора; порожній — docblock-типу немає
     * @param array<string, string> $declaringNamespaces `use`-namespaces класу, що оголошує властивість
     * @param bool $docStrict strict mode для docblock-типу
     */
    public function __construct(
        readonly public string $docType,
        readonly public ?ReflectionType $nativeType,
        readonly public array $declaringNamespaces,
        readonly public bool $docStrict,
    ) {}
}
