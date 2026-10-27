<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests;

use Ufo\DTO\Transformer\Context\TransformationContext;
use Ufo\DTO\Transformer\Hydrator\HydratorChainInterface;

final readonly class StaticInstanceIsolationHydratorChain implements HydratorChainInterface
{
    public function __construct(private string $valuePrefix)
    {
    }

    public function resolve(array $schema, mixed $value, TransformationContext $context): mixed
    {
        return $this->valuePrefix . ':' . $value;
    }
}
