<?php

declare(strict_types=1);

namespace Ufo\DTO\Tests\Support;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerFromArrayFactory;
use Ufo\DTO\Factory\DefaultDTOTransformerToArrayFactory;
use Ufo\DTO\Factory\DefaultMetadataFactory;
use Ufo\DTO\Factory\DefaultNormalizerChainFactory;
use Ufo\DTO\Factory\DefaultSchemaFactory;
use Ufo\DTO\Schema\ClassSchemaProviderInterface;
use Ufo\DTO\Schema\DefaultValueResolver;
use Ufo\DTO\Schema\Meta\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Schema\Meta\ReflectionMetadataProviderInterface;
use Ufo\DTO\Schema\Meta\RuntimeReflectionCache;
use Ufo\DTO\Schema\Meta\StrictModeResolverInterface;
use Ufo\DTO\Schema\Type\ReflectionTypeSchemaResolver;
use Ufo\DTO\Schema\Type\TypeSchemaResolverInterface;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\DTOFromArrayTransformer;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\Hydrator\CompiledTypeSchemaProviderInterface;
use Ufo\DTO\Transformer\Hydrator\HydratorChain;
use Ufo\DTO\Transformer\Hydrator\HydratorChainInterface;
use Ufo\DTO\Transformer\Normalizer\NormalizerChainInterface;

final class TransformerFactory
{
    public static function runtimeCache(?CacheInterface $cache = null): RuntimeReflectionCache
    {
        return (new DefaultMetadataFactory())->createRuntimeReflectionCache($cache);
    }

    public static function keyGenerator(): ReflectionMetadataKeyGenerator
    {
        return (new DefaultMetadataFactory())->createReflectionMetadataKeyGenerator();
    }

    public static function metadataProvider(
        ?CacheInterface $cache = null,
        ?RuntimeReflectionCache $runtimeCache = null,
    ): ReflectionMetadataProviderInterface {
        return (new DefaultMetadataFactory())->createMetadataProvider($runtimeCache ?? self::runtimeCache($cache));
    }

    public static function strictModeResolver(
        ?CacheInterface $cache = null,
        ?RuntimeReflectionCache $runtimeCache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
    ): StrictModeResolverInterface {
        $runtimeCache ??= self::runtimeCache($cache);
        $metadataProvider ??= self::metadataProvider(runtimeCache: $runtimeCache);

        return (new DefaultMetadataFactory())->createStrictModeResolver($metadataProvider, $runtimeCache);
    }

    public static function typeSchemaResolver(
        ?CacheInterface $cache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
        ?RuntimeReflectionCache $runtimeCache = null,
    ): TypeSchemaResolverInterface {
        $runtimeCache ??= self::runtimeCache($cache);
        $metadataProvider ??= self::metadataProvider(runtimeCache: $runtimeCache);

        return new ReflectionTypeSchemaResolver(
            self::strictModeResolver(runtimeCache: $runtimeCache, metadataProvider: $metadataProvider),
            $metadataProvider,
        );
    }

    public static function schemaProvider(
        ?CacheInterface $cache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
        ?RuntimeReflectionCache $runtimeCache = null,
    ): ClassSchemaProviderInterface {
        $runtimeCache ??= self::runtimeCache($cache);
        $metadataProvider ??= self::metadataProvider(runtimeCache: $runtimeCache);

        return (new DefaultSchemaFactory())->createClassSchemaProvider(
            $metadataProvider,
            self::typeSchemaResolver(metadataProvider: $metadataProvider, runtimeCache: $runtimeCache),
            $cache,
        );
    }

    public static function normalizerChain(
        ?CacheInterface $cache = null,
        ?ReflectionMetadataProviderInterface $metadataProvider = null,
        ?RuntimeReflectionCache $runtimeCache = null,
    ): NormalizerChainInterface {
        return (new DefaultNormalizerChainFactory(
            self::schemaProvider($cache, $metadataProvider, $runtimeCache),
            new DateTimeConverter(),
        ))->create();
    }

    /**
     * Аргументи конструктора `DefaultDTOTransformerFromArrayFactory` — для тестових підкласів фабрики.
     *
     * @return array{TypeSchemaResolverInterface, ReflectionMetadataProviderInterface, ClassSchemaProviderInterface, ValidatorInterface, DateTimeConverter}
     */
    public static function fromArrayFactoryArguments(
        ?CacheInterface $cache = null,
        ?ValidatorInterface $validator = null,
    ): array {
        $runtimeCache = self::runtimeCache($cache);
        $metadataProvider = self::metadataProvider(runtimeCache: $runtimeCache);

        return [
            self::typeSchemaResolver(
                metadataProvider: $metadataProvider,
                runtimeCache: $runtimeCache,
            ),
            $metadataProvider,
            self::schemaProvider($cache, $metadataProvider, $runtimeCache),
            $validator ?? Validation::createValidator(),
            new DateTimeConverter(),
        ];
    }

    public static function fromArrayFactory(
        ?CacheInterface $cache = null,
        ?ValidatorInterface $validator = null,
    ): DefaultDTOTransformerFromArrayFactory {
        return new DefaultDTOTransformerFromArrayFactory(...self::fromArrayFactoryArguments($cache, $validator));
    }

    public static function fromArrayTransformer(iterable $transformers = []): DTOFromArrayTransformerInterface
    {
        return self::fromArrayFactory()->create($transformers);
    }

    public static function hydratorChain(): HydratorChainInterface
    {
        $factory = new class (...self::fromArrayFactoryArguments()) extends DefaultDTOTransformerFromArrayFactory {
            public ?HydratorChain $chain = null;

            protected function createTransformer(
                HydratorChain $chain,
                CompiledTypeSchemaProviderInterface $typeSchemaProvider,
                DefaultValueResolver $defaultValueResolver,
            ): DTOFromArrayTransformer {
                $this->chain = $chain;

                return parent::createTransformer($chain, $typeSchemaProvider, $defaultValueResolver);
            }
        };
        $factory->create();

        return $factory->chain;
    }

    public static function transformer(): DTOTransformer
    {
        $arguments = self::fromArrayFactoryArguments();
        [, , $schemaProvider] = $arguments;
        $fromArrayFactory = new DefaultDTOTransformerFromArrayFactory(...$arguments);
        $toArrayFactory = new DefaultDTOTransformerToArrayFactory(
            new DefaultNormalizerChainFactory(
                $schemaProvider,
                new DateTimeConverter(),
            ),
        );

        return (new DefaultDTOTransformerFactory($fromArrayFactory, $toArrayFactory))->create();
    }
}
