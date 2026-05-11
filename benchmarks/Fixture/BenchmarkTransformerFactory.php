<?php

declare(strict_types=1);

namespace Ufo\DTO\Benchmarks\Fixture;

use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerToArrayFactory;
use Ufo\DTO\Factory\DefaultMetadataFactory;
use Ufo\DTO\Factory\DefaultPropertyNormalizerFactory;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\DTOToArrayTransformerInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\Metadata\AttributeSerializationContextProvider;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataProvider;
use Ufo\DTO\Transformer\Metadata\StrictModeResolver;
use Ufo\DTO\Transformer\Type\ReflectionTypeSchemaResolver;

final readonly class BenchmarkTransformerFactory
{
    private DefaultMetadataFactory $metadataFactory;
    private ReflectionMetadataKeyGeneratorInterface $keyGenerator;
    private ReflectionMetadataProviderInterface $metadataProvider;
    private TypeSchemaResolverInterface $typeSchemaResolver;
    private DefaultPropertyNormalizerFactory $propertyNormalizerFactory;

    public function __construct(
        private RuntimeReflectionCacheInterface $runtimeReflectionCache,
    ) {
        $this->metadataFactory = new class ($this->runtimeReflectionCache) extends DefaultMetadataFactory {
            public function __construct(
                private readonly RuntimeReflectionCacheInterface $runtimeReflectionCache,
            ) {}

            public function createRuntimeReflectionCache(?CacheInterface $cache = null): RuntimeReflectionCacheInterface
            {
                return $this->runtimeReflectionCache;
            }
        };
        $this->keyGenerator = $this->metadataFactory->createReflectionMetadataKeyGenerator();
        $this->metadataProvider = new ReflectionMetadataProvider(
            $this->metadataFactory->createDocBlockParser($this->runtimeReflectionCache),
            $this->keyGenerator,
            $this->runtimeReflectionCache,
        );
        $this->typeSchemaResolver = new ReflectionTypeSchemaResolver(
            new StrictModeResolver(
                $this->metadataProvider,
                $this->keyGenerator,
                $this->runtimeReflectionCache,
            ),
            $this->metadataProvider,
            $this->keyGenerator,
            $this->runtimeReflectionCache,
        );
        $this->propertyNormalizerFactory = new DefaultPropertyNormalizerFactory(
            $this->metadataProvider,
            new AttributeSerializationContextProvider(),
            new DateTimeConverter(),
        );
    }

    public function createFacadeTransformer(): DTOTransformer
    {
        return (new DefaultDTOTransformerFactory(
            new DefaultDTOTransformerFromArrayFactory(
                $this->typeSchemaResolver,
                $this->metadataProvider,
                $this->keyGenerator,
            ),
            new DefaultDTOTransformerToArrayFactory($this->propertyNormalizerFactory),
        ))->create();
    }

    public function createFromArrayTransformer(): DTOFromArrayTransformerInterface
    {
        return (new DefaultDTOTransformerFromArrayFactory(
            $this->typeSchemaResolver,
            $this->metadataProvider,
            $this->keyGenerator,
        ))->create();
    }

    public function createToArrayTransformer(): DTOToArrayTransformerInterface
    {
        return (new DefaultDTOTransformerToArrayFactory(
            $this->propertyNormalizerFactory,
        ))->create();
    }
}
