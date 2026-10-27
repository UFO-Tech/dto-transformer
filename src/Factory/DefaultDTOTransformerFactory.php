<?php

declare(strict_types=1);

namespace Ufo\DTO\Factory;

use ReflectionClass;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Schema\Meta\RuntimeReflectionCache;
use Ufo\DTO\Schema\Type\ReflectionTypeSchemaResolver;
use Ufo\DTO\Transformer\Converter\DateTimeConverter;
use Ufo\DTO\Transformer\DTOFromArrayTransformerInterface;
use Ufo\DTO\Transformer\DTOToArrayTransformerInterface;

final class DefaultDTOTransformerFactory implements DTOTransformerFactoryInterface
{
    protected ?DTOFromArrayTransformerInterface $fromArrayTransformer = null;
    protected ?DTOToArrayTransformerInterface $toArrayTransformer = null;

    public function __construct(
        protected DTOTransformerFromArrayFactoryInterface $fromArrayFactory,
        protected DTOTransformerToArrayFactoryInterface $toArrayFactory,
    ) {}

    /**
     * Нова стандартна композиція на кожен виклик; спільний екземпляр тримає викликач.
     *
     * @param CacheInterface|null $persistentCache
     * @param int $runtimeCacheLimit
     * @param ValidatorInterface|null $validator
     * @return DefaultDTOTransformerFactory
     */
    public static function default(
        ?CacheInterface $persistentCache = null,
        int $runtimeCacheLimit = RuntimeReflectionCache::DEFAULT_LIMIT,
        ?ValidatorInterface $validator = null,
    ): self
    {
        $metadataFactory = new DefaultMetadataFactory();
        $runtimeCache = $metadataFactory->createRuntimeReflectionCache($persistentCache, $runtimeCacheLimit);
        $metadataProvider = $metadataFactory->createMetadataProvider($runtimeCache);

        $typeSchemaResolver = new ReflectionTypeSchemaResolver(
            strictModeResolver: $metadataFactory->createStrictModeResolver($metadataProvider, $runtimeCache),
            metadataProvider: $metadataProvider,
        );

        $schemaProvider = new DefaultSchemaFactory()->createClassSchemaProvider($metadataProvider, $typeSchemaResolver, $persistentCache);
        $dateTimeValueConverter = new DateTimeConverter();

        $propertyNormalizerFactory = new DefaultNormalizerChainFactory(
            schemaProvider: $schemaProvider,
            dateTimeValueConverter: $dateTimeValueConverter,
        );

        return new self(
            fromArrayFactory: new DefaultDTOTransformerFromArrayFactory(
                typeSchemaResolver: $typeSchemaResolver,
                metadataProvider: $metadataProvider,
                schemaProvider: $schemaProvider,
                validator: $validator ?? self::lazyValidator(),
                dateTimeValueConverter: $dateTimeValueConverter,
            ),
            toArrayFactory: new DefaultDTOTransformerToArrayFactory(
                propertyNormalizerFactory: $propertyNormalizerFactory,
            ),
        );
    }

    /**
     * Фасади з однієї фабрики ділять один граф трансформерів і його кеші.
     */
    public function create(string $class = DTOTransformer::class): DTOTransformer
    {
        return new $class(
            fromArrayTransformer: $this->fromArrayTransformer ??= $this->fromArrayFactory->create(),
            toArrayTransformer: $this->toArrayTransformer ??= $this->toArrayFactory->create(),
        );
    }

    /**
     * Symfony validator створюється при першій перевірці, а не при складанні.
     */
    private static function lazyValidator(): ValidatorInterface
    {
        return new ReflectionClass(RecursiveValidator::class)->newLazyProxy(
            static fn (): ValidatorInterface => Validation::createValidator(),
        );
    }
}
