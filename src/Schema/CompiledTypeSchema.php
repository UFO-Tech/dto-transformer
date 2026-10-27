<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use Ufo\DTO\Schema\Type\ResolvedTypeSchema;

/**
 * Схема типу властивості для одного набору namespaces разом зі скомпільованим вузлом гідраторів.
 */
final class CompiledTypeSchema
{
    /**
     * @param ?SchemaNode $node null — значення проходить через ланцюг гідраторів
     */
    public function __construct(
        readonly public ResolvedTypeSchema $resolved,
        readonly public ?SchemaNode $node,
    ) {}
}
