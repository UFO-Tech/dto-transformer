<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Support;

use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerToArrayFactory;
use Ufo\DTO\Factory\DefaultMetadataFactory;
use Ufo\DTO\Factory\DefaultPropertyNormalizerFactory;
use Ufo\DTO\Interfaces\DTOFromArrayTransformerInterface;
use Ufo\DTO\Interfaces\Hydrator\ParamHydratorChainInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataKeyGeneratorInterface;
use Ufo\DTO\Interfaces\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Interfaces\Meta\RuntimeReflectionCacheInterface;
use Ufo\DTO\Interfaces\Meta\StrictModeResolverInterface;
use Ufo\DTO\Interfaces\Meta\TypeSchemaResolverInterface;
use Ufo\DTO\Interfaces\Normalizer\PropertyNormalizerChainInterface;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
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
use Ufo\DTO\Transformer\Metadata\AttributeSerializationContextProvider;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataProvider;
use Ufo\DTO\Transformer\Metadata\StrictModeResolver;
use Ufo\DTO\Transformer\Type\ReflectionTypeSchemaResolver;

final class TransformerFactory
{
    public static function runtimeCache(?CacheInterface $cache = null): RuntimeReflectionCacheInterface
    {
        return (new DefaultMetadataFactory())->createRuntimeReflectionCache($cache);
    }

    public static function keyGenerator(): ReflectionMetadataKeyGeneratorInterface
    {
        return (new DefaultMetadataFactory())->createReflectionMetadataKeyGenerator();
    }

    public static function metadataProvider(
        ?CacheInterface $cache = null,
        ?RuntimeReflectionCacheInterface $runtimeCache = null,
        ?ReflectionMetadataKeyGeneratorInterface $keyGenerator = null,
    ): ReflectionMetadataProviderInterface {
        $metadataFactory = new DefaultMetadataFactory();
        $runtimeCache ??= $metadataFactory->createRuntimeReflectionCache($cache);
        $keyGenerator ??= $metadataFactory->createReflectionMetadataKeyGenerator();

        return new ReflectionMetadataProvider(
            $metadataFactory->createDocBlockParser($runtimeCache),
            $keyGenerator,
            $runtimeCache,
        );
    }

    public static function strictModeResolver(
        ?CacheInterface $cache = null,
        ?RuntimeReflectionCacheInterface $runtimeCache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
        ?ReflectionMetadataKeyGeneratorInterface $keyGenerator = null,
    ): StrictModeResolverInterface {
        $runtimeCache ??= self::runtimeCache($cache);
        $keyGenerator ??= self::keyGenerator();
        $metadataProvider ??= self::metadataProvider(runtimeCache: $runtimeCache, keyGenerator: $keyGenerator);

        return new StrictModeResolver($metadataProvider, $keyGenerator, $runtimeCache);
    }

    public static function typeSchemaResolver(
        ?CacheInterface $cache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
        ?RuntimeReflectionCacheInterface $runtimeCache = null,
        ?ReflectionMetadataKeyGeneratorInterface $keyGenerator = null,
    ): TypeSchemaResolverInterface {
        $runtimeCache ??= self::runtimeCache($cache);
        $keyGenerator ??= self::keyGenerator();
        $metadataProvider ??= self::metadataProvider(runtimeCache: $runtimeCache, keyGenerator: $keyGenerator);
        $strictModeResolver = self::strictModeResolver(
            runtimeCache: $runtimeCache,
            metadataProvider: $metadataProvider,
            keyGenerator: $keyGenerator,
        );

        return new ReflectionTypeSchemaResolver(
            $strictModeResolver,
            $metadataProvider,
            $keyGenerator,
            $runtimeCache,
        );
    }

    public static function propertyNormalizer(
        ?CacheInterface $cache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
        ?RuntimeReflectionCacheInterface $runtimeCache = null,
    ): PropertyNormalizerChainInterface {
        $metadataProvider ??= self::metadataProvider($cache, $runtimeCache);

        return (new DefaultPropertyNormalizerFactory(
            $metadataProvider,
            new AttributeSerializationContextProvider(),
            new DateTimeConverter(),
        ))->create();
    }

    public static function fromArrayTransformer(iterable $transformers = []): DTOFromArrayTransformerInterface
    {
        $runtimeCache = self::runtimeCache();
        $keyGenerator = self::keyGenerator();
        $metadataProvider = self::metadataProvider(runtimeCache: $runtimeCache, keyGenerator: $keyGenerator);

        return (new DefaultDTOTransformerFromArrayFactory(
            self::typeSchemaResolver(
                metadataProvider: $metadataProvider,
                runtimeCache: $runtimeCache,
                keyGenerator: $keyGenerator,
            ),
            $metadataProvider,
            $keyGenerator,
        ))->create($transformers);
    }

    public static function paramHydrator(): ParamHydratorChainInterface
    {
        $runtimeCache = self::runtimeCache();
        $keyGenerator = self::keyGenerator();
        $metadataProvider = self::metadataProvider(runtimeCache: $runtimeCache, keyGenerator: $keyGenerator);
        $paramHydrator = new ParamHydrator();
        $transformer = new DTOFromArrayTransformer(
            hydratorChain: $paramHydrator,
            typeSchemaResolver: self::typeSchemaResolver(
                metadataProvider: $metadataProvider,
                runtimeCache: $runtimeCache,
                keyGenerator: $keyGenerator,
            ),
            metadataProvider: $metadataProvider,
            keyGenerator: $keyGenerator,
        );

        return $paramHydrator->addHydrators([
            new UnionParamHydrator(),
            new EnumParamHydrator(),
            new ScalarParamHydrator(),
            new ReflectionClassHydrator(),
            new ReflectionParameterHydrator(),
            new ReflectionPropertyHydrator(),
            new DateTimeHydrator(),
            new DtoHydrator($transformer),
            new ArrayItemsHydrator(),
            new AdditionalHydrator(),
            new MixedHydrator(),
        ]);
    }

    public static function transformer(): DTOTransformer
    {
        $runtimeCache = self::runtimeCache();
        $keyGenerator = self::keyGenerator();
        $metadataProvider = self::metadataProvider(runtimeCache: $runtimeCache, keyGenerator: $keyGenerator);

        $fromArrayFactory = new DefaultDTOTransformerFromArrayFactory(
            self::typeSchemaResolver(
                metadataProvider: $metadataProvider,
                runtimeCache: $runtimeCache,
                keyGenerator: $keyGenerator,
            ),
            $metadataProvider,
            $keyGenerator,
        );
        $toArrayFactory = new DefaultDTOTransformerToArrayFactory(
            new DefaultPropertyNormalizerFactory(
                $metadataProvider,
                new AttributeSerializationContextProvider(),
                new DateTimeConverter(),
            ),
        );

        return (new DefaultDTOTransformerFactory($fromArrayFactory, $toArrayFactory))->create();
    }
}
