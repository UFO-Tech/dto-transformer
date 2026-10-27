<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Schema\SchemaNode;
use Ufo\DTO\Transformer\Context\TransformationContext;

/**
 * Гідратор, що бере дочірні схеми з уже скомпільованого вузла, а не вибирає гідратор на кожне значення.
 */
interface SchemaNodeHydratorInterface extends HydratorInterface
{
    public function resolveNode(
        SchemaNode $node,
        mixed $value,
        TransformationContext $context,
    ): mixed;
}
