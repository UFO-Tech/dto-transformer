<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\Factory\DTOTransformerFromArrayFactoryInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\Hydrator\AdditionalHydrator;
use Ufo\DTO\Transformer\Hydrator\ArrayItemsHydrator;
use Ufo\DTO\Transformer\Hydrator\DateTimeHydrator;
use Ufo\DTO\Transformer\Hydrator\DtoHydrator;
use Ufo\DTO\Transformer\Hydrator\EnumParamHydrator;
use Ufo\DTO\Transformer\Hydrator\MixedHydrator;
use Ufo\DTO\Transformer\Hydrator\ParamHydrator;
use Ufo\DTO\Transformer\Hydrator\ReflectionClassHydrator;
use Ufo\DTO\Transformer\Hydrator\ReflectionParameterHydrator;
use Ufo\DTO\Transformer\Hydrator\ReflectionPropertyHydrator;
use Ufo\DTO\Transformer\Hydrator\ScalarParamHydrator;
use Ufo\DTO\Transformer\Hydrator\UnionParamHydrator;

class DefaultDTOTransformerFromArrayFactory implements DTOTransformerFromArrayFactoryInterface
{
    public function __construct(
        protected TypeSchemaResolverInterface $typeSchemaResolver,
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected ReflectionMetadataKeyGeneratorInterface $keyGenerator,
    ) {}

    public function create(iterable $transformers = []): DTOFromArrayTransformerInterface
    {
        $hydrator = new ParamHydrator();

        $transformer = new DTOFromArrayTransformer(
            hydratorChain: $hydrator,
            typeSchemaResolver: $this->typeSchemaResolver,
            metadataProvider: $this->metadataProvider,
            keyGenerator: $this->keyGenerator,
        );

        $hydrator->addHydrators([
            new UnionParamHydrator(),
            new EnumParamHydrator(),
            new ScalarParamHydrator(),
            new ReflectionClassHydrator(),
            new ReflectionParameterHydrator(),
            new ReflectionPropertyHydrator(),
            new DateTimeHydrator(),
            new DtoHydrator($transformer, $transformers),
            new ArrayItemsHydrator(),
            new AdditionalHydrator(),
            new MixedHydrator(),
        ]);

        return $transformer;
    }
}