<?php

declare(strict_types = 1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\BaseDTOFromArrayTransformer;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;

abstract class AbstractHydrator implements HydratorInterface
{
    protected ?HydratorChainInterface $chainHydrator = null;

    public function setChainHydrator(HydratorChainInterface $chainHydrator): void
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

    /** Схема зібрана з даних payload, тож не потрапляє в кеш ланцюга. */
    protected function resolveNestedUncached(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        if ($this->chainHydrator instanceof HydratorChain) {
            return $this->chainHydrator->resolveUncached($schema, $value, $context) ?? $value;
        }

        return $this->resolveNested($schema, $value, $context);
    }

    protected function type(array $schema): ?string
    {
        return $schema[TypeHintResolver::TYPE] ?? null;
    }
}
