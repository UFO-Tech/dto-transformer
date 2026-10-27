<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\DTOToArrayTransformerInterface;

/**
 * Свіжий граф трансформерів на кожен rebuild(): кеші попереднього графа зникають разом із ним.
 */
final class TransformerGraph
{
    private DTOTransformer $facade;

    public function __construct(
        private readonly ?CacheInterface $persistentCache = null,
    ) {
        $this->rebuild();
    }

    public function rebuild(): void
    {
        $this->facade = DefaultDTOTransformerFactory::default($this->persistentCache)->create();
        DTOTransformer::reset();
        DTOTransformer::boot($this->facade);
    }

    public function fromArrayTransformer(): DTOFromArrayTransformerInterface
    {
        return (fn (): DTOFromArrayTransformerInterface => $this->fromArrayTransformer)->call($this->facade);
    }

    public function toArrayTransformer(): DTOToArrayTransformerInterface
    {
        return (fn (): DTOToArrayTransformerInterface => $this->toArrayTransformer)->call($this->facade);
    }
}
