<?php

declare(strict_types=1);

namespace Ufo\DTO\Transformer\Hydrator;

use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorInterface;
use Ufo\DTO\VO\TransformationContext;

use function array_key_exists;
use function md5;
use function serialize;

class ParamHydrator implements ParamHydratorChainInterface
{
    /**
     * @var array<string, ParamHydratorInterface|null>
     */
    protected array $hydratorBySchema = [];

    /**
     * @var list<ParamHydratorInterface>
     */
    protected array $hydrators = [];

    /**
     * @param iterable<ParamHydratorInterface> $hydrators
     */
    public function __construct(iterable $hydrators = [])
    {
        $this->addHydrators($hydrators);
    }

    public function addHydrator(ParamHydratorInterface $hydrator): self
    {
        $this->hydratorBySchema = [];

        if ($hydrator instanceof AbstractParamHydrator) {
            $hydrator->setChainHydrator($this);
        }

        $this->hydrators[] = $hydrator;

        return $this;
    }

    /**
     * @param iterable<ParamHydratorInterface> $hydrators
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
        $schemaKey = $this->schemaKey($schema);
        if (array_key_exists($schemaKey, $this->hydratorBySchema)) {
            return $this->hydratorBySchema[$schemaKey]?->resolve($schema, $value, $context) ?? $value;
        }

        foreach ($this->hydrators as $resolver) {
            if (!$resolver->supports($schema)) continue;
            $this->hydratorBySchema[$schemaKey] = $resolver;
            return $resolver->resolve($schema, $value, $context);
        }

        $this->hydratorBySchema[$schemaKey] = null;

        return $value;
    }

    protected function schemaKey(array $schema): string
    {
        return md5(json_encode($schema));
    }
}
