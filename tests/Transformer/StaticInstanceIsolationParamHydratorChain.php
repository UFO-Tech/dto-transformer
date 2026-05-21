<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Transformer;

use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;
use Ufo\DTO\VO\TransformationContext;

final readonly class StaticInstanceIsolationParamHydratorChain implements ParamHydratorChainInterface
{
    public function __construct(private string $valuePrefix)
    {
    }

    public function resolve(array $schema, mixed $value, TransformationContext $context): mixed
    {
        return $this->valuePrefix . ':' . $value;
    }
}
