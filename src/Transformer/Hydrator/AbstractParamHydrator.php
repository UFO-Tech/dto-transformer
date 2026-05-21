<?php

declare(strict_types = 1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

abstract class AbstractParamHydrator implements ParamHydratorInterface
{
    protected ?ParamHydratorChainInterface $chainHydrator = null;

    public function setChainHydrator(ParamHydratorChainInterface $chainHydrator): void
    {
        $this->chainHydrator = $chainHydrator;
    }

    protected function resolveNested(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        return $this->chainHydrator?->resolve($schema, $value, $context) ?? $value;
    }

    protected function type(array $schema): ?string
    {
        return $schema[TypeHintResolver::TYPE] ?? null;
    }
}
