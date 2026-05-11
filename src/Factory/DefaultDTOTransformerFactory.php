<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Interfaces\Factory\DTOTransformerFactoryInterface;
use Ufo\DTO\Interfaces\Factory\DTOTransformerFromArrayFactoryInterface;
use Ufo\DTO\Interfaces\Factory\DTOTransformerToArrayFactoryInterface;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\Metadata\AttributeSerializationContextProvider;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataKeyGenerator;
use Ufo\DTO\Transformer\Metadata\ReflectionMetadataProvider;
use Ufo\DTO\Transformer\Metadata\RuntimeReflectionCache;
use Ufo\DTO\Transformer\Metadata\StrictModeResolver;
use Ufo\DTO\Transformer\Type\ReflectionTypeSchemaResolver;

final class DefaultDTOTransformerFactory implements DTOTransformerFactoryInterface
{
    protected static ?self $defaultInstance = null;

    public function __construct(
        protected DTOTransformerFromArrayFactoryInterface $fromArrayFactory,
        protected DTOTransformerToArrayFactoryInterface $toArrayFactory,
    ) {}

    public static function default(?CacheInterface $persistentCache = null, bool $recreate = false): self
    {
        if (self::$defaultInstance && !$recreate) {
            return static::$defaultInstance;
        }

        $runtimeCache = new RuntimeReflectionCache(persistentCache: $persistentCache);
        $keyGenerator = new ReflectionMetadataKeyGenerator();

        $metadataProvider = new ReflectionMetadataProvider(
            docBlockParser: (new DefaultMetadataFactory())->createDocBlockParser($runtimeCache),
            keyGenerator: $keyGenerator,
            runtimeCache: $runtimeCache
        );

        $strictModeResolver = new StrictModeResolver(
            metadataProvider: $metadataProvider,
            keyGenerator: $keyGenerator,
            runtimeCache: $runtimeCache,
        );

        $typeSchemaResolver = new ReflectionTypeSchemaResolver(
            strictModeResolver: $strictModeResolver,
            metadataProvider: $metadataProvider,
            keyGenerator: $keyGenerator,
            runtimeCache: $runtimeCache,
        );

        $propertyNormalizerFactory = new DefaultPropertyNormalizerFactory(
            metadataProvider: $metadataProvider,
            serializationContextProvider: new AttributeSerializationContextProvider(),
            dateTimeValueConverter: new DateTimeConverter(),
        );

        return new self(
            fromArrayFactory: new DefaultDTOTransformerFromArrayFactory(
                typeSchemaResolver: $typeSchemaResolver,
                metadataProvider: $metadataProvider,
                keyGenerator: $keyGenerator,
            ),
            toArrayFactory: new DefaultDTOTransformerToArrayFactory(
                propertyNormalizerFactory: $propertyNormalizerFactory,
            ),
        );
    }

    public function create(string $class = DTOTransformer::class): DTOTransformer
    {
        return new $class(
            fromArrayTransformer: $this->fromArrayFactory->create(),
            toArrayTransformer: $this->toArrayFactory->create(),
        );
    }
}
