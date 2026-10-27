<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Schema\DefaultValueResolver;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\RequiredKeysCandidateFilter;
use Ufo\DTO\Schema\Type\TypeCompatibility;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;
use Ufo\DTO\Transformer\Attribute\AssertionsValidator;
use Ufo\DTO\Transformer\Attribute\AttrDTOResolver;
use Ufo\DTO\Transformer\Converter\DateTimeValueConverterInterface;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\Hydrator\AdditionalHydrator;
use Ufo\DTO\Transformer\Hydrator\ArrayItemsHydrator;
use Ufo\DTO\Transformer\Hydrator\Cache\CachedCompiledTypeSchemaProvider;
use Ufo\DTO\Transformer\Hydrator\Cache\CachedHydratorChain;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProvider;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProviderInterface;
use Ufo\DTO\Transformer\Hydrator\DateTimeHydrator;
use Ufo\DTO\Transformer\Hydrator\DTOHydrator;
use Ufo\DTO\Transformer\Hydrator\EnumHydrator;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorInterface;
use Ufo\DTO\Transformer\Hydrator\MixedHydrator;
use Ufo\DTO\Transformer\Hydrator\ReflectionClassHydrator;
use Ufo\DTO\Transformer\Hydrator\ReflectionParameterHydrator;
use Ufo\DTO\Transformer\Hydrator\ReflectionPropertyHydrator;
use Ufo\DTO\Transformer\Hydrator\ScalarHydrator;
use Ufo\DTO\Transformer\Hydrator\UnionHydrator;
use Ufo\DTO\Transformer\SmartClassResolver;

class DefaultDTOTransformerFromArrayFactory implements DTOTransformerFromArrayFactoryInterface
{
    public function __construct(
        protected TypeSchemaResolverInterface $typeSchemaResolver,
        protected ReflectionMetadataProviderInterface $metadataProvider,
        protected ClassSchemaProviderInterface $schemaProvider,
        protected ValidatorInterface $validator,
        protected DateTimeValueConverterInterface $dateTimeValueConverter,
    ) {}

    public function create(iterable $transformers = []): DTOFromArrayTransformerInterface
    {
        $chain = new CachedHydratorChain();
        $transformer = $this->createTransformer(
            $chain,
            $this->createTypeSchemaProvider($chain),
            new DefaultValueResolver(new TypeCompatibility(), $this->metadataProvider),
        );
        $chain->addHydrators($this->createHydrators($transformer, $transformers));

        return $transformer;
    }

    protected function createTypeSchemaProvider(HydratorChain $chain): CompiledTypeSchemaProviderInterface
    {
        return new CachedCompiledTypeSchemaProvider(new CompiledTypeSchemaProvider($this->schemaProvider, $chain));
    }

    protected function createTransformer(
        HydratorChain $chain,
        CompiledTypeSchemaProviderInterface $typeSchemaProvider,
        DefaultValueResolver $defaultValueResolver,
    ): DTOFromArrayTransformer
    {
        return new DTOFromArrayTransformer(
            hydratorChain: $chain,
            typeSchemaResolver: $this->typeSchemaResolver,
            schemaProvider: $this->schemaProvider,
            typeSchemaProvider: $typeSchemaProvider,
            defaultValueResolver: $defaultValueResolver,
            smartClassResolver: new SmartClassResolver(),
            candidateFilter: new RequiredKeysCandidateFilter($this->schemaProvider),
            assertionsValidator: new AssertionsValidator($this->validator),
            attrDTOResolver: new AttrDTOResolver(),
        );
    }

    /**
     * @param iterable<DTOFromArrayTransformerInterface> $transformers
     * @return list<HydratorInterface>
     */
    protected function createHydrators(DTOFromArrayTransformerInterface $transformer, iterable $transformers): array
    {
        return [
            new UnionHydrator(),
            new EnumHydrator(),
            new ScalarHydrator(),
            new ReflectionClassHydrator(),
            new ReflectionParameterHydrator(),
            new ReflectionPropertyHydrator(),
            new DateTimeHydrator($this->dateTimeValueConverter),
            new DTOHydrator($transformer, $transformers),
            new ArrayItemsHydrator(),
            new AdditionalHydrator(),
            new MixedHydrator(),
        ];
    }
}
