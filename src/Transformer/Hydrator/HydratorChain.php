<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Schema\SchemaNode;
use Ufo\DTO\Schema\Type\TypeHintResolver;
use Ufo\DTO\Transformer\Context\TransformationContext;

use function is_array;

/**
 * Ланцюг гідраторів: компілює схему у вузол на кожен виклик; кешує вузли {@see Cache\CachedHydratorChain}.
 */
class HydratorChain implements HydratorChainInterface
{
    /**
     * @var list<HydratorInterface>
     */
    protected array $hydrators = [];

    /**
     * @param iterable<HydratorInterface> $hydrators
     */
    public function __construct(iterable $hydrators = [])
    {
        $this->addHydrators($hydrators);
    }

    public function addHydrator(HydratorInterface $hydrator): self
    {
        if ($hydrator instanceof AbstractHydrator) {
            $hydrator->setChainHydrator($this);
        }

        $this->hydrators[] = $hydrator;

        return $this;
    }

    /**
     * @param iterable<HydratorInterface> $hydrators
     */
    public function addHydrators(iterable $hydrators): self
    {
        foreach ($hydrators as $resolver) {
            $this->addHydrator($resolver);
        }

        return $this;
    }

    public function resolve(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        return $this->node($schema)->resolve($value, $context);
    }

    /** Скомпільований вузол для схеми з коду. */
    public function node(array $schema): SchemaNode
    {
        return $this->compile($schema);
    }

    public function hydratorFor(array $schema): ?HydratorInterface
    {
        return $this->node($schema)->hydrator;
    }

    /** Вибір гідратора без кешу верхнього вузла — для схем, зібраних із даних payload. */
    public function resolveUncached(
        array $schema,
        mixed $value,
        TransformationContext $context,
    ): mixed
    {
        return $this->compile($schema)->resolve($value, $context);
    }

    protected function compile(array $schema): SchemaNode
    {
        $items = $schema[TypeHintResolver::ITEMS] ?? null;
        $additional = $schema[TypeHintResolver::ADDITIONAL_PROPERTIES] ?? null;
        $branches = [];
        $allowsNull = false;
        foreach ($schema[TypeHintResolver::ONE_OFF] ?? [] as $branch) {
            $branches[] = $this->node($branch);
            $allowsNull = $allowsNull || ($branch[TypeHintResolver::TYPE] ?? null) === TypeHintResolver::NULL->value;
        }

        return new SchemaNode(
            schema: $schema,
            hydrator: $this->findHydrator($schema),
            items: is_array($items) ? $this->node($items) : null,
            additional: is_array($additional) ? $this->node($additional) : null,
            branches: $branches,
            allowsNull: $allowsNull,
        );
    }

    protected function findHydrator(array $schema): ?HydratorInterface
    {
        foreach ($this->hydrators as $resolver) {
            if ($resolver->supports($schema)) {
                return $resolver;
            }
        }

        return null;
    }
}
