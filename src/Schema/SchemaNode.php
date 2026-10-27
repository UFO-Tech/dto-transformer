<?php

declare(strict_types=1);

namespace Ufo\DTO\Schema;

use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Transformer\Hydrator\SchemaNodeHydratorInterface;

/**
 * Схема з наперед вибраним гідратором і скомпільованими дочірніми схемами `items`, `additionalProperties`, `oneOf`.
 */
final class SchemaNode
{
    /**
     * @param list<SchemaNode> $branches
     */
    public function __construct(
        readonly public array $schema,
        readonly public ?HydratorInterface $hydrator,
        readonly public ?SchemaNode $items = null,
        readonly public ?SchemaNode $additional = null,
        readonly public array $branches = [],
        readonly public bool $allowsNull = false,
    ) {}

    public function resolve(mixed $value, TransformationContext $context): mixed
    {
        if ($this->hydrator instanceof SchemaNodeHydratorInterface) {
            return $this->hydrator->resolveNode($this, $value, $context) ?? $value;
        }

        return $this->hydrator?->resolve($this->schema, $value, $context) ?? $value;
    }
}
